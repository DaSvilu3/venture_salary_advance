<?php

namespace App\Domain\Onboarding;

use App\Contracts\BankGateway;
use App\Contracts\PayrollVerifier;
use App\Enums\EnrolmentStatus;
use App\Enums\MandateStatus;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Mandate;
use App\Models\User;
use App\Support\Clock;
use App\Support\Money;
use DomainException;

/**
 * Registers the employee's direct-debit mandate with the employer's bank, verifies their salary with
 * payroll and sends the enrolment to HR for approval.
 */
class SignMandate
{
    /**
     * The most a single collection under the mandate may take.
     */
    private const MANDATE_CEILING_OMR = 1000;

    public function __construct(
        private readonly BankGateway $bank,
        private readonly PayrollVerifier $payroll,
        private readonly Clock $clock,
    ) {}

    /**
     * @throws DomainException when the bank refuses the mandate.
     */
    public function handle(Employee $employee, User $actor): Mandate
    {
        $maxAmount = Money::omr(self::MANDATE_CEILING_OMR);
        $registration = $this->bank->registerMandate($employee, $maxAmount);

        if (! $registration->succeeded) {
            throw new DomainException(__('The bank could not register your mandate. Please check your IBAN and try again.'));
        }

        $employee->mandates()->where('status', MandateStatus::Active)->update([
            'status' => MandateStatus::Cancelled,
            'cancelled_at' => $this->clock->now(),
        ]);

        $mandate = $employee->mandates()->create([
            'bank_partner_id' => $employee->employer->bank_partner_id,
            'reference' => $registration->reference,
            'max_amount_baisa' => $maxAmount,
            'status' => MandateStatus::Active,
            'signed_at' => $this->clock->now(),
        ]);

        $verification = $this->payroll->verifySalary($employee);

        $employee->update([
            'salary_verified_at' => $verification->succeeded ? $this->clock->now() : null,
            'enrolment_status' => $employee->enrolment_status === EnrolmentStatus::Invited
                ? EnrolmentStatus::PendingApproval
                : $employee->enrolment_status,
        ]);

        AuditLog::record('employee.mandate_signed', $employee, $actor, [
            'mandate_reference' => $mandate->reference,
            'salary_verified' => $verification->succeeded,
        ]);

        return $mandate;
    }
}
