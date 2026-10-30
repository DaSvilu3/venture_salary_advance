<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdvanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdvanceResource;
use App\Models\Advance;
use App\Models\AuditLog;
use App\Models\Employer;
use App\Models\LedgerEntry;
use App\Models\ProgramTerms;
use App\Support\Clock;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdvanceController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(AdvanceStatus::class)],
            'employer' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $advances = Advance::query()
            ->with(['employee:id,name,staff_no', 'employer:id,name'])
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['employer'] ?? null, fn ($query, int $employerId) => $query->where('employer_id', $employerId))
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(
                fn ($query) => $query->where('reference', 'like', "%{$search}%")
                    ->orWhereRelation('employee', 'name', 'like', "%{$search}%"),
            ))
            ->latest('requested_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/advances/index', [
            'advances' => AdvanceResource::collection($advances),
            'filters' => [
                'status' => $filters['status'] ?? '',
                'employer' => isset($filters['employer']) ? (string) $filters['employer'] : '',
                'search' => $filters['search'] ?? '',
            ],
            'employers' => Employer::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => array_column(AdvanceStatus::cases(), 'value'),
        ]);
    }

    public function show(Advance $advance): Response
    {
        $advance->load(['employee', 'employer', 'bankPartner:id,name', 'funderPartner:id,name', 'disclosure:id,version', 'mandate:id,reference']);

        return Inertia::render('admin/advances/show', [
            'advance' => [
                ...AdvanceResource::make($advance)->resolve(),
                'bank' => $advance->bankPartner?->name,
                'funder' => $advance->funderPartner?->name,
                'disclosure_version' => $advance->disclosure->version,
                'mandate_reference' => $advance->mandate?->reference,
            ],
            'allowedActions' => [
                'approve' => $advance->status === AdvanceStatus::Requested,
                'cancel' => $advance->status->canTransitionTo(AdvanceStatus::Cancelled),
                'retry_collection' => in_array($advance->status, AdvanceStatus::outstanding(), true),
                'write_off' => $advance->status->canTransitionTo(AdvanceStatus::WrittenOff),
            ],
            'timeline' => AuditLog::query()
                ->whereMorphedTo('subject', $advance)
                ->with('actor:id,name')
                ->oldest('id')
                ->get()
                ->map(fn (AuditLog $log): array => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'actor' => $log->actor?->name,
                    'meta' => $log->meta,
                    'at' => $log->created_at?->toIso8601String(),
                ]),
            'transactions' => $advance->ledgerEntries()->oldest('id')->get()->map(fn (LedgerEntry $entry): array => [
                'id' => $entry->id,
                'type' => $entry->type->value,
                'status' => $entry->status->value,
                'amount' => $entry->amount_baisa,
                'provider' => $entry->provider,
                'reference' => $entry->external_reference,
                'failure_reason' => $entry->failure_reason,
                'at' => $entry->occurred_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * The formal, print-ready record of a single advance, assembled for a regulatory review (CBO).
     *
     * It gathers every party, the eligibility basis, the fully disclosed fee and accepted terms, the
     * direct-debit mandate, the repayment and the complete audit trail into one document. Personal
     * identifiers (civil ID, IBAN) are masked before they leave the server.
     */
    public function ticket(Request $request, Advance $advance, Clock $clock): Response
    {
        $advance->load(['employee', 'employer', 'bankPartner', 'funderPartner', 'disclosure', 'mandate', 'payCycle']);

        $employee = $advance->employee;
        $employer = $advance->employer;
        $terms = ProgramTerms::forEmployer($employer);
        $locale = app()->getLocale();

        $limit = $employee->net_salary_baisa
            ->multiplyBasisPoints($terms->limit_ratio_bps)
            ->min($terms->max_limit_baisa);

        return Inertia::render('admin/advances/ticket', [
            'advance' => [
                ...AdvanceResource::make($advance)->resolve(),
                'disclosure_accepted_at' => $advance->disclosure_accepted_at?->toIso8601String(),
            ],
            'employee' => [
                'name' => $employee->name,
                'staff_no' => $employee->staff_no,
                'masked_civil_id' => $employee->maskedCivilId(),
                'masked_iban' => $this->maskIban($employee->iban),
                'net_salary' => $employee->net_salary_baisa->baisa,
                'employment_start_date' => $employee->employment_start_date->toDateString(),
                'employment_status' => $employee->employment_status->value,
                'salary_verified_at' => $employee->salary_verified_at?->toIso8601String(),
            ],
            'employer' => [
                'name' => $employer->name,
                'cr_number' => $employer->cr_number,
            ],
            'bank' => $advance->bankPartner === null ? null : [
                'name' => $advance->bankPartner->name,
                'code' => $advance->bankPartner->code,
            ],
            'funder' => $advance->funderPartner === null ? null : [
                'name' => $advance->funderPartner->name,
                'code' => $advance->funderPartner->code,
            ],
            'mandate' => $advance->mandate === null ? null : [
                'reference' => $advance->mandate->reference,
                'status' => $advance->mandate->status->value,
                'signed_at' => $advance->mandate->signed_at?->toIso8601String(),
                'max_amount' => $advance->mandate->max_amount_baisa->baisa,
            ],
            'disclosure' => [
                'version' => $advance->disclosure->version,
                'body' => $advance->disclosure->bodyFor($locale),
            ],
            'terms' => [
                'limit_ratio_bps' => $terms->limit_ratio_bps,
                'fee_model' => $terms->fee_model->value,
                'flat_fee' => $terms->flat_fee_baisa->baisa,
                'limit' => $limit->baisa,
            ],
            'payCycle' => $advance->payCycle === null ? null : [
                'period_start' => $advance->payCycle->period_start->toDateString(),
                'payday' => $advance->payCycle->payday->toDateString(),
            ],
            'timeline' => AuditLog::query()
                ->whereMorphedTo('subject', $advance)
                ->with('actor:id,name')
                ->oldest('id')
                ->get()
                ->map(fn (AuditLog $log): array => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'actor' => $log->actor?->name,
                    'at' => $log->created_at?->toIso8601String(),
                ]),
            'transactions' => $advance->ledgerEntries()->oldest('id')->get()->map(fn (LedgerEntry $entry): array => [
                'id' => $entry->id,
                'type' => $entry->type->value,
                'status' => $entry->status->value,
                'amount' => $entry->amount_baisa,
                'reference' => $entry->external_reference,
                'failure_reason' => $entry->failure_reason,
                'at' => $entry->occurred_at->toIso8601String(),
            ]),
            'generated' => [
                'on' => $clock->today()->toDateString(),
                'by' => $request->user()?->name,
            ],
        ]);
    }

    /**
     * Mask an IBAN for display, keeping only the country prefix and the last four characters.
     */
    private function maskIban(?string $iban): ?string
    {
        if ($iban === null || $iban === '') {
            return null;
        }

        $kept = 2;
        $visibleTail = 4;
        $hidden = max(0, mb_strlen($iban) - $kept - $visibleTail);

        return mb_substr($iban, 0, $kept).str_repeat('•', $hidden).mb_substr($iban, -$visibleTail);
    }
}
