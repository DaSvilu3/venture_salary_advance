<?php

namespace Tests\Feature\Domain\Eligibility;

use App\Domain\Eligibility\EligibilityService;
use App\Domain\Payroll\PayCycleCalendar;
use App\Enums\AdvanceStatus;
use App\Enums\IneligibilityReason;
use App\Enums\MandateStatus;
use App\Models\Advance;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\PayCycle;
use App\Models\ProgramTerms;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EligibilityServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Payday is the 25th, so the last request date is the 22nd.
        $this->travelTo(CarbonImmutable::parse('2026-10-10 10:00'));
    }

    public function test_enrolled_employee_can_draw_the_salary_ratio_up_to_the_cap(): void
    {
        $employee = $this->enrolledEmployee(Money::omr(800));

        $result = app(EligibilityService::class)->check($employee);

        $this->assertTrue($result->isEligible());
        $this->assertSame(150_000, $result->limit->baisa);
        $this->assertSame(150_000, $result->available->baisa);
        $this->assertSame('2026-10-25', $result->payday->toDateString());
        $this->assertSame('2026-10-22', $result->lastRequestDate->toDateString());
    }

    public function test_limit_is_capped_at_the_maximum_for_high_salaries(): void
    {
        $employee = $this->enrolledEmployee(Money::omr(5000));

        $result = app(EligibilityService::class)->check($employee);

        $this->assertSame(500_000, $result->limit->baisa);
    }

    public function test_employer_terms_override_the_platform_defaults(): void
    {
        $employee = $this->enrolledEmployee(Money::omr(800));
        ProgramTerms::factory()->for($employee->employer)->create(['limit_ratio_bps' => 2500]);

        $result = app(EligibilityService::class)->check($employee);

        $this->assertSame(200_000, $result->limit->baisa);
    }

    public function test_advances_this_cycle_reduce_what_is_available(): void
    {
        $employee = $this->enrolledEmployee(Money::omr(800));
        $this->advanceThisCycle($employee, Money::omr(100), AdvanceStatus::Disbursed);
        $this->advanceThisCycle($employee, Money::omr(30), AdvanceStatus::DisbursementFailed);

        $result = app(EligibilityService::class)->check($employee);

        $this->assertTrue($result->isEligible());
        $this->assertSame(100_000, $result->drawn->baisa);
        $this->assertSame(50_000, $result->available->baisa);
        $this->assertSame(1, $result->drawsThisCycle);
    }

    public function test_limit_reached_when_less_than_the_minimum_remains(): void
    {
        $employee = $this->enrolledEmployee(Money::omr(800));
        $this->advanceThisCycle($employee, Money::omr(145), AdvanceStatus::Disbursed);

        $result = app(EligibilityService::class)->check($employee);

        $this->assertSame([IneligibilityReason::LimitReached], $result->reasons);
    }

    public function test_max_draws_per_cycle_blocks_further_advances(): void
    {
        $employee = $this->enrolledEmployee(Money::omr(800));

        foreach (range(1, 3) as $draw) {
            $this->advanceThisCycle($employee, Money::omr(10), AdvanceStatus::Disbursed);
        }

        $result = app(EligibilityService::class)->check($employee);

        $this->assertSame([IneligibilityReason::MaxDrawsReached], $result->reasons);
    }

    public function test_requests_after_the_cutoff_wait_until_after_payday(): void
    {
        $employee = $this->enrolledEmployee(Money::omr(800));

        $this->travelTo(CarbonImmutable::parse('2026-10-23 08:00'));
        $this->assertSame([IneligibilityReason::CutoffPassed], app(EligibilityService::class)->check($employee)->reasons);

        $this->travelTo(CarbonImmutable::parse('2026-10-26 08:00'));
        $nextCycle = app(EligibilityService::class)->check($employee);
        $this->assertTrue($nextCycle->isEligible());
        $this->assertSame('2026-11-25', $nextCycle->payday->toDateString());
    }

    public function test_unpaid_advance_from_an_earlier_cycle_blocks_new_advances(): void
    {
        $employee = $this->enrolledEmployee(Money::omr(800));
        $earlierCycle = PayCycle::factory()->for($employee->employer)->create(['payday' => '2026-09-25']);
        Advance::factory()->for($employee)->for($earlierCycle)->inStatus(AdvanceStatus::Overdue)->create();

        $result = app(EligibilityService::class)->check($employee);

        $this->assertSame([IneligibilityReason::HasUnpaidAdvance], $result->reasons);
        $this->assertSame(150_000, $result->available->baisa);
    }

    public function test_reports_every_failed_onboarding_rule(): void
    {
        $employee = Employee::factory()
            ->for(Employer::factory()->pending())
            ->startedOn('2026-09-01')
            ->onNotice()
            ->create();

        $result = app(EligibilityService::class)->check($employee);

        $this->assertSame([
            IneligibilityReason::EmployerNotActive,
            IneligibilityReason::NotEnrolled,
            IneligibilityReason::EmploymentNotActive,
            IneligibilityReason::SalaryNotVerified,
            IneligibilityReason::NoActiveMandate,
            IneligibilityReason::TenureTooShort,
        ], $result->reasons);
    }

    public function test_cancelled_mandate_blocks_advances(): void
    {
        $employee = $this->enrolledEmployee(Money::omr(800));
        $employee->mandates()->update(['status' => MandateStatus::Cancelled]);

        $result = app(EligibilityService::class)->check($employee->fresh());

        $this->assertSame([IneligibilityReason::NoActiveMandate], $result->reasons);
    }

    private function enrolledEmployee(Money $salary): Employee
    {
        return Employee::factory()
            ->for(Employer::factory()->paydayOn(25))
            ->enrolled()
            ->earning($salary)
            ->create();
    }

    private function advanceThisCycle(Employee $employee, Money $amount, AdvanceStatus $status): Advance
    {
        $cycle = app(PayCycleCalendar::class)->cycleEndingOn($employee->employer, CarbonImmutable::parse('2026-10-25'));

        return Advance::factory()->for($employee)->for($cycle)->forAmount($amount)->inStatus($status)->create();
    }
}
