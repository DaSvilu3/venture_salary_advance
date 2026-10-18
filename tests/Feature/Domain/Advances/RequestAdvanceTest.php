<?php

namespace Tests\Feature\Domain\Advances;

use App\Domain\Advances\AdvanceNotAllowed;
use App\Domain\Advances\RequestAdvance;
use App\Enums\AdvanceStatus;
use App\Jobs\DisburseAdvance;
use App\Models\Disclosure;
use App\Models\Employee;
use App\Models\Employer;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RequestAdvanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-10 10:00'));
    }

    public function test_creates_an_approved_advance_due_on_payday_and_queues_the_payout(): void
    {
        $employee = $this->enrolledEmployee();
        $disclosure = Disclosure::factory()->create();
        Queue::fake([DisburseAdvance::class]);

        $advance = app(RequestAdvance::class)->handle($employee, Money::omr(100), $disclosure, $employee->user);

        $this->assertSame(AdvanceStatus::Approved, $advance->status);
        $this->assertSame(100_000, $advance->amount_baisa->baisa);
        $this->assertSame(2_500, $advance->fee_baisa->baisa);
        $this->assertSame(102_500, $advance->total_due_baisa->baisa);
        $this->assertSame('2026-10-25', $advance->due_date->toDateString());
        $this->assertTrue($advance->disclosure->is($disclosure));
        $this->assertSame($employee->activeMandate->id, $advance->mandate_id);
        $this->assertSame(
            ['advance.requested', 'advance.approved'],
            $advance->auditLogs()->orderBy('id')->pluck('action')->all(),
        );
        Queue::assertPushed(DisburseAdvance::class, fn (DisburseAdvance $job): bool => $job->advanceId === $advance->id);
    }

    public function test_rejects_amounts_above_what_is_available(): void
    {
        $employee = $this->enrolledEmployee();
        Queue::fake([DisburseAdvance::class]);

        $this->expectExceptionObject(AdvanceNotAllowed::aboveAvailable(Money::omr(150)));

        try {
            app(RequestAdvance::class)->handle($employee, Money::omr(151), Disclosure::factory()->create(), $employee->user);
        } finally {
            $this->assertSame(0, $employee->advances()->count());
            Queue::assertNotPushed(DisburseAdvance::class);
        }
    }

    public function test_rejects_amounts_below_the_minimum(): void
    {
        $employee = $this->enrolledEmployee();

        $this->expectExceptionObject(AdvanceNotAllowed::belowMinimum(Money::omr(10)));

        app(RequestAdvance::class)->handle($employee, Money::omr('9.999'), Disclosure::factory()->create(), $employee->user);
    }

    public function test_second_request_is_limited_to_what_remains_in_the_cycle(): void
    {
        $employee = $this->enrolledEmployee();
        $disclosure = Disclosure::factory()->create();
        Queue::fake([DisburseAdvance::class]);
        app(RequestAdvance::class)->handle($employee, Money::omr(100), $disclosure, $employee->user);

        $this->expectExceptionObject(AdvanceNotAllowed::aboveAvailable(Money::omr(50)));

        app(RequestAdvance::class)->handle($employee, Money::omr(60), $disclosure, $employee->user);
    }

    public function test_rejects_ineligible_employees_with_the_first_reason(): void
    {
        $employee = $this->enrolledEmployee();
        $employee->update(['employment_status' => 'terminated']);

        $this->expectException(AdvanceNotAllowed::class);
        $this->expectExceptionMessage('Advances are not available during a notice period or after leaving.');

        app(RequestAdvance::class)->handle($employee, Money::omr(50), Disclosure::factory()->create(), $employee->user);
    }

    public function test_rejects_acceptance_of_an_outdated_disclosure(): void
    {
        $employee = $this->enrolledEmployee();
        $outdated = Disclosure::factory()->create(['published_at' => now()->subMonth()]);
        Disclosure::factory()->create(['published_at' => now()]);

        $this->expectExceptionObject(AdvanceNotAllowed::staleDisclosure());

        app(RequestAdvance::class)->handle($employee, Money::omr(50), $outdated, $employee->user);
    }

    private function enrolledEmployee(): Employee
    {
        return Employee::factory()
            ->for(Employer::factory()->paydayOn(25))
            ->enrolled()
            ->earning(Money::omr(800))
            ->create();
    }
}
