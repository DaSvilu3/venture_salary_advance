<?php

namespace App\Domain\Eligibility;

use App\Domain\Payroll\PayCycleCalendar;
use App\Enums\AdvanceStatus;
use App\Enums\EmploymentStatus;
use App\Enums\IneligibilityReason;
use App\Models\Employee;
use App\Models\ProgramTerms;
use App\Support\Clock;
use App\Support\Money;

/**
 * Applies the program rules to decide whether, and how much, an employee can draw this pay cycle.
 */
class EligibilityService
{
    public function __construct(
        private readonly Clock $clock,
        private readonly PayCycleCalendar $calendar,
    ) {}

    public function check(Employee $employee): EligibilityResult
    {
        $employer = $employee->employer;
        $terms = ProgramTerms::forEmployer($employer);
        $today = $this->clock->today();
        $payday = $this->calendar->paydayOnOrAfter($employer, $today);
        $lastRequestDate = $this->calendar->lastRequestDate($payday, $terms->cutoff_days_before_payday);

        $cycleAdvances = $employee->advances()
            ->whereIn('status', AdvanceStatus::drawnAgainstLimit())
            ->whereHas('payCycle', fn ($cycle) => $cycle->whereDate('payday', $payday))
            ->get(['id', 'amount_baisa']);

        $drawn = $cycleAdvances->reduce(fn (Money $sum, $advance): Money => $sum->plus($advance->amount_baisa), Money::zero());
        $limit = $employee->net_salary_baisa->multiplyBasisPoints($terms->limit_ratio_bps)->min($terms->max_limit_baisa);
        $available = $limit->minus($drawn)->max(Money::zero());

        $reasons = [];

        if (! $employer->isActive()) {
            $reasons[] = IneligibilityReason::EmployerNotActive;
        }

        if (! $employee->isEnrolled()) {
            $reasons[] = IneligibilityReason::NotEnrolled;
        }

        if ($employee->employment_status !== EmploymentStatus::Active) {
            $reasons[] = IneligibilityReason::EmploymentNotActive;
        }

        if ($employee->salary_verified_at === null) {
            $reasons[] = IneligibilityReason::SalaryNotVerified;
        }

        if ($employee->activeMandate === null) {
            $reasons[] = IneligibilityReason::NoActiveMandate;
        }

        if ($employee->employment_start_date->addMonthsNoOverflow($terms->min_tenure_months)->greaterThan($today)) {
            $reasons[] = IneligibilityReason::TenureTooShort;
        }

        if ($employee->advances()->whereIn('status', AdvanceStatus::unpaid())->exists()) {
            $reasons[] = IneligibilityReason::HasUnpaidAdvance;
        }

        if ($today->greaterThan($lastRequestDate)) {
            $reasons[] = IneligibilityReason::CutoffPassed;
        }

        if ($cycleAdvances->count() >= $terms->max_draws_per_cycle) {
            $reasons[] = IneligibilityReason::MaxDrawsReached;
        }

        if ($available->lessThan($terms->min_advance_baisa)) {
            $reasons[] = IneligibilityReason::LimitReached;
        }

        return new EligibilityResult(
            reasons: $reasons,
            limit: $limit,
            drawn: $drawn,
            available: $available,
            payday: $payday,
            lastRequestDate: $lastRequestDate,
            drawsThisCycle: $cycleAdvances->count(),
            terms: $terms,
        );
    }
}
