<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdvanceStatus;
use App\Enums\EmployerStatus;
use App\Enums\EnrolmentStatus;
use App\Enums\PartnerType;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employer;
use App\Models\EmployerAgreement;
use App\Models\EmployerDocument;
use App\Models\Partner;
use App\Models\ProgramTerms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmployerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/employers/index', [
            'employers' => Employer::query()
                ->with(['bankPartner:id,name', 'funderPartner:id,name'])
                ->withCount([
                    'employees',
                    'employees as enrolled_count' => fn ($query) => $query->where('enrolment_status', EnrolmentStatus::Enrolled),
                ])
                ->withSum(['advances as outstanding_baisa' => fn ($query) => $query->whereIn('status', AdvanceStatus::outstanding())], 'total_due_baisa')
                ->orderBy('name')
                ->get()
                ->map(fn (Employer $employer): array => [
                    'id' => $employer->id,
                    'name' => $employer->name,
                    'cr_number' => $employer->cr_number,
                    'status' => $employer->status->value,
                    'payday_day' => $employer->payday_day,
                    'bank' => $employer->bankPartner?->name,
                    'funder' => $employer->funderPartner?->name,
                    'employees_count' => $employer->employees_count,
                    'enrolled_count' => $employer->enrolled_count,
                    'outstanding' => (int) $employer->outstanding_baisa,
                ]),
            'partners' => $this->partnerOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cr_number' => ['required', 'string', 'max:20', 'unique:employers,cr_number'],
            'payday_day' => ['required', 'integer', 'between:1,31'],
            'bank_partner_id' => ['required', Rule::exists('partners', 'id')->where('type', PartnerType::Bank)],
            'funder_partner_id' => ['required', Rule::exists('partners', 'id')->where('type', PartnerType::Funder)],
        ]);

        $employer = Employer::query()->create([...$data, 'status' => EmployerStatus::Pending, 'subscription_plan' => 'standard']);
        AuditLog::record('employer.created', $employer, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was created. Activate it when the contract is signed.', ['name' => $employer->name])]);

        return to_route('admin.employers.show', $employer);
    }

    public function show(Employer $employer): Response
    {
        $override = $employer->programTerms;

        return Inertia::render('admin/employers/show', [
            'employer' => [
                'id' => $employer->id,
                'name' => $employer->name,
                'cr_number' => $employer->cr_number,
                'status' => $employer->status->value,
                'payday_day' => $employer->payday_day,
                'bank_partner_id' => $employer->bank_partner_id,
                'funder_partner_id' => $employer->funder_partner_id,
                'employees_count' => $employer->employees()->count(),
            ],
            'admins' => $employer->admins()->get(['id', 'name', 'email'])->toArray(),
            'documents' => $employer->documents()
                ->latest()
                ->get()
                ->map(fn (EmployerDocument $document): array => $document->toPortalArray()),
            'agreement' => (function (Employer $employer): array {
                $agreement = EmployerAgreement::forEmployer($employer);

                return [
                    'status' => $agreement->status->value,
                    'decided_at' => $agreement->decided_at?->toIso8601String(),
                    'version' => $agreement->version,
                    'has_signed' => $agreement->signed_path !== null,
                ];
            })($employer),
            'terms' => ProgramTermsController::present(ProgramTerms::forEmployer($employer)),
            'hasOverride' => $override !== null,
            'partners' => $this->partnerOptions(),
        ]);
    }

    public function update(Request $request, Employer $employer): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(EmployerStatus::class)],
            'payday_day' => ['required', 'integer', 'between:1,31'],
            'bank_partner_id' => ['required', Rule::exists('partners', 'id')->where('type', PartnerType::Bank)],
            'funder_partner_id' => ['required', Rule::exists('partners', 'id')->where('type', PartnerType::Funder)],
        ]);

        $employer->fill($data);
        $changed = array_keys($employer->getDirty());
        $employer->save();

        if ($changed !== []) {
            AuditLog::record('employer.updated', $employer, $request->user(), ['changed' => $changed]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Employer saved.')]);

        return to_route('admin.employers.show', $employer);
    }

    /**
     * @return array{banks: array<int, array{id: int, name: string}>, funders: array<int, array{id: int, name: string}>}
     */
    private function partnerOptions(): array
    {
        $partners = Partner::query()->orderBy('name')->get(['id', 'name', 'type']);

        $options = fn (PartnerType $type): array => $partners
            ->where('type', $type)
            ->map(fn (Partner $partner): array => ['id' => $partner->id, 'name' => $partner->name])
            ->values()
            ->all();

        return ['banks' => $options(PartnerType::Bank), 'funders' => $options(PartnerType::Funder)];
    }
}
