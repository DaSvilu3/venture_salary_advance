<?php

namespace App\Domain\Advances;

use App\Domain\Eligibility\EligibilityService;
use App\Domain\Payroll\PayCycleCalendar;
use App\Enums\AdvanceStatus;
use App\Jobs\DisburseAdvance;
use App\Models\Advance;
use App\Models\AuditLog;
use App\Models\Disclosure;
use App\Models\Employee;
use App\Models\ProgramTerms;
use App\Models\User;
use App\Support\Clock;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates an advance for an employee after re-checking every program rule, then queues the payout.
 */
class RequestAdvance
{
    public function __construct(
        private readonly EligibilityService $eligibility,
        private readonly FeeCalculator $fees,
        private readonly PayCycleCalendar $calendar,
        private readonly AdvanceLifecycle $lifecycle,
        private readonly Clock $clock,
    ) {}

    /**
     * @throws AdvanceNotAllowed
     */
    public function handle(Employee $employee, Money $amount, Disclosure $acceptedDisclosure, User $actor): Advance
    {
        $advance = DB::transaction(function () use ($employee, $amount, $acceptedDisclosure, $actor): Advance {
            // Serialise requests for the same employee so two quick taps cannot both pass the limit check.
            $employee = Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            if (! $acceptedDisclosure->is(Disclosure::current())) {
                throw AdvanceNotAllowed::staleDisclosure();
            }

            $eligibility = $this->eligibility->check($employee);

            if (! $eligibility->isEligible()) {
                throw AdvanceNotAllowed::ineligible($eligibility->reasons);
            }

            if ($amount->lessThan($eligibility->terms->min_advance_baisa)) {
                throw AdvanceNotAllowed::belowMinimum($eligibility->terms->min_advance_baisa);
            }

            if ($amount->greaterThan($eligibility->available)) {
                throw AdvanceNotAllowed::aboveAvailable($eligibility->available);
            }

            $fee = $this->fees->feeFor($amount, $eligibility->terms);
            $now = $this->clock->now();

            $advance = Advance::query()->create([
                'reference' => 'SLA-'.Str::upper(Str::random(8)),
                'employee_id' => $employee->id,
                'employer_id' => $employee->employer_id,
                'pay_cycle_id' => $this->calendar->cycleEndingOn($employee->employer, $eligibility->payday)->id,
                'mandate_id' => $employee->activeMandate->id,
                'bank_partner_id' => $employee->employer->bank_partner_id,
                'funder_partner_id' => $employee->employer->funder_partner_id,
                'disclosure_id' => $acceptedDisclosure->id,
                'amount_baisa' => $amount,
                'fee_baisa' => $fee,
                'total_due_baisa' => $amount->plus($fee),
                'due_date' => $eligibility->payday,
                'status' => AdvanceStatus::Requested,
                'disclosure_accepted_at' => $now,
                'requested_at' => $now,
            ]);

            AuditLog::record('advance.requested', $advance, $actor, [
                'amount_baisa' => $amount->baisa,
                'fee_baisa' => $fee->baisa,
                'disclosure_version' => $acceptedDisclosure->version,
            ]);

            if (! ProgramTerms::forEmployer($employee->employer)->auto_approve) {
                return $advance;
            }

            return $this->lifecycle->transition($advance, AdvanceStatus::Approved, meta: ['by' => 'rules']);
        });

        if ($advance->status === AdvanceStatus::Approved) {
            DisburseAdvance::dispatch($advance->id);
        }

        return $advance;
    }
}
