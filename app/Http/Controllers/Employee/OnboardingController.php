<?php

namespace App\Http\Controllers\Employee;

use App\Domain\Onboarding\SignMandate;
use App\Enums\MandateStatus;
use App\Http\Requests\Employee\UpdateBankAccountRequest;
use App\Rules\OmanIban;
use App\Support\Clock;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends EmployeePortalController
{
    /**
     * The onboarding checklist: bank account, direct-debit mandate, HR approval.
     */
    public function show(Request $request): Response
    {
        $employee = $this->employee($request);
        $mandate = $employee->activeMandate;

        return Inertia::render('app/onboarding', [
            'employer' => $employee->employer->name,
            'bank' => $employee->employer->bankPartner?->name,
            'iban' => $employee->iban,
            'mandate' => $mandate === null ? null : [
                'reference' => $mandate->reference,
                'max_amount' => $mandate->max_amount_baisa,
                'signed_at' => $mandate->signed_at?->toIso8601String(),
            ],
            'enrolmentStatus' => $employee->enrolment_status->value,
            'salaryVerified' => $employee->salary_verified_at !== null,
        ]);
    }

    /**
     * Save the account advances are paid into and collected from. A new account needs a new mandate.
     */
    public function updateBankAccount(UpdateBankAccountRequest $request, Clock $clock): RedirectResponse
    {
        $employee = $this->employee($request);
        $iban = OmanIban::normalize($request->string('iban')->toString());

        if ($employee->iban !== $iban) {
            $employee->mandates()->where('status', MandateStatus::Active)->update([
                'status' => MandateStatus::Cancelled,
                'cancelled_at' => $clock->now(),
            ]);
        }

        $employee->update(['iban' => $iban]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bank account saved.')]);

        return to_route('app.onboarding');
    }

    /**
     * Sign the direct-debit mandate with the bank.
     */
    public function storeMandate(Request $request, SignMandate $signMandate): RedirectResponse
    {
        $employee = $this->employee($request);

        if ($employee->iban === null) {
            throw ValidationException::withMessages(['mandate' => __('Add your bank account first.')]);
        }

        try {
            $signMandate->handle($employee, $request->user());
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['mandate' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Direct-debit mandate signed.')]);

        return to_route($employee->fresh()?->isEnrolled() ? 'app.home' : 'app.onboarding');
    }
}
