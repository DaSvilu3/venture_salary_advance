<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdvanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdvanceResource;
use App\Models\Advance;
use App\Models\AuditLog;
use App\Models\Employer;
use App\Models\LedgerEntry;
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
}
