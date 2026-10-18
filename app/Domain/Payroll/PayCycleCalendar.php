<?php

namespace App\Domain\Payroll;

use App\Enums\PayCycleStatus;
use App\Models\Employer;
use App\Models\PayCycle;
use Carbon\CarbonImmutable;

/**
 * Works out an employer's paydays and pay cycles. Paydays on the 29th–31st fall on the last day of
 * shorter months.
 */
class PayCycleCalendar
{
    /**
     * The next payday on or after the given date.
     */
    public function paydayOnOrAfter(Employer $employer, CarbonImmutable $date): CarbonImmutable
    {
        $date = $date->startOfDay();
        $payday = $this->paydayInMonth($employer, $date);

        return $date->lessThanOrEqualTo($payday)
            ? $payday
            : $this->paydayInMonth($employer, $date->startOfMonth()->addMonthNoOverflow());
    }

    /**
     * The last day an advance can be requested against the cycle ending on the given payday.
     */
    public function lastRequestDate(CarbonImmutable $payday, int $cutoffDaysBeforePayday): CarbonImmutable
    {
        return $payday->subDays($cutoffDaysBeforePayday);
    }

    /**
     * The stored pay cycle ending on the given payday, created on first use.
     */
    public function cycleEndingOn(Employer $employer, CarbonImmutable $payday): PayCycle
    {
        $previousPayday = $this->paydayInMonth($employer, $payday->startOfMonth()->subMonthNoOverflow());

        return PayCycle::query()
            ->where('employer_id', $employer->id)
            ->whereDate('payday', $payday)
            ->first()
            ?? PayCycle::query()->create([
                'employer_id' => $employer->id,
                'payday' => $payday,
                'period_start' => $previousPayday->addDay(),
                'status' => PayCycleStatus::Open,
            ]);
    }

    private function paydayInMonth(Employer $employer, CarbonImmutable $dateInMonth): CarbonImmutable
    {
        $day = min($employer->payday_day, $dateInMonth->daysInMonth);

        return $dateInMonth->setDay($day)->startOfDay();
    }
}
