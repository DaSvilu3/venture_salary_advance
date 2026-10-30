<?php

namespace App\Http\Controllers\Employee;

use App\Domain\Advances\AdvanceNotAllowed;
use App\Domain\Advances\RequestAdvance;
use App\Domain\Eligibility\EligibilityService;
use App\Http\Requests\Employee\StoreAdvanceRequest;
use App\Http\Resources\AdvanceResource;
use App\Models\AuditLog;
use App\Models\Disclosure;
use App\Models\LedgerEntry;
use App\Models\ProgramTerms;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdvanceController extends EmployeePortalController
{
    public function index(Request $request): Response
    {
        $employee = $this->employee($request);

        return Inertia::render('app/advances/index', [
            'advances' => AdvanceResource::collection(
                $employee->advances()->latest('requested_at')->latest('id')->paginate(15),
            ),
        ]);
    }

    public function create(Request $request, EligibilityService $eligibility): Response|RedirectResponse
    {
        $employee = $this->employee($request);

        if (! $this->hasFinishedOnboarding($employee)) {
            return to_route('app.onboarding');
        }

        $disclosure = Disclosure::current();

        return Inertia::render('app/advances/create', [
            'eligibility' => $eligibility->check($employee)->toArray(),
            'disclosure' => $disclosure === null ? null : [
                'id' => $disclosure->id,
                'version' => $disclosure->version,
                'body' => $disclosure->bodyFor(app()->getLocale()),
            ],
            'bank' => $employee->employer->bankPartner?->name,
            'iban' => $employee->iban,
            'sla_hours' => ProgramTerms::forEmployer($employee->employer)->disbursement_sla_hours,
        ]);
    }

    public function store(StoreAdvanceRequest $request, RequestAdvance $requestAdvance): RedirectResponse
    {
        $employee = $this->employee($request);
        $disclosure = Disclosure::query()->findOrFail($request->integer('disclosure_id'));

        try {
            $advance = $requestAdvance->handle(
                $employee,
                Money::omr($request->string('amount')->toString()),
                $disclosure,
                $request->user(),
            );
        } catch (AdvanceNotAllowed $exception) {
            throw ValidationException::withMessages(['amount' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Advance confirmed. The money is on its way to your account.')]);

        return to_route('app.advances.show', $advance->reference);
    }

    public function show(Request $request, string $reference): Response
    {
        $advance = $this->employee($request)->advances()->where('reference', $reference)->firstOrFail();

        return Inertia::render('app/advances/show', [
            'advance' => AdvanceResource::make($advance)->resolve(),
            'timeline' => $advance->auditLogs()->oldest('id')->get()->map(fn (AuditLog $log): array => [
                'id' => $log->id,
                'status' => str_replace('advance.', '', $log->action),
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
        ]);
    }
}
