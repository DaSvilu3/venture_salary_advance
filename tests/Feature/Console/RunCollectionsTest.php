<?php

namespace Tests\Feature\Console;

use App\Domain\Advances\RequestAdvance;
use App\Enums\AdvanceStatus;
use App\Enums\PayCycleStatus;
use App\Models\Disclosure;
use App\Models\Employee;
use App\Models\Employer;
use App\Services\Fake\FakeScenarios;
use App\Support\Clock;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RunCollectionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_full_lifecycle_from_request_to_collection_on_the_simulated_payday(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 10:00'));
        $employee = $this->enrolledEmployee();

        $advance = app(RequestAdvance::class)->handle($employee, Money::omr(100), Disclosure::factory()->create(), $employee->user);
        $this->assertSame(AdvanceStatus::Disbursed, $advance->fresh()->status);

        $this->artisan('advances:collect')->assertSuccessful();
        $this->assertSame(AdvanceStatus::Disbursed, $advance->fresh()->status, 'Nothing is collected before payday.');

        app(Clock::class)->travelTo(CarbonImmutable::parse('2026-10-25'));
        $this->artisan('advances:collect')
            ->expectsOutputToContain('Queued 1 collections')
            ->assertSuccessful();

        $this->assertSame(AdvanceStatus::Collected, $advance->fresh()->status);
    }

    public function test_failed_collection_is_retried_then_marked_overdue(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 10:00'));
        $employee = $this->enrolledEmployee();
        $advance = app(RequestAdvance::class)->handle($employee, Money::omr(100), Disclosure::factory()->create(), $employee->user);
        app(FakeScenarios::class)->failNext(FakeScenarios::COLLECTION, $employee, times: 3);
        $clock = app(Clock::class);

        $clock->travelTo(CarbonImmutable::parse('2026-10-25'));
        $this->artisan('advances:collect');
        $this->assertSame(AdvanceStatus::CollectionFailed, $advance->fresh()->status);

        $clock->travelTo(CarbonImmutable::parse('2026-10-26'));
        $this->artisan('advances:collect');
        $this->assertSame(1, $advance->fresh()->collection_attempts, 'The retry waits two days.');

        $clock->travelTo(CarbonImmutable::parse('2026-10-27'));
        $this->artisan('advances:collect');
        $clock->travelTo(CarbonImmutable::parse('2026-10-29'));
        $this->artisan('advances:collect');

        $advance->refresh();
        $this->assertSame(AdvanceStatus::Overdue, $advance->status);
        $this->assertSame(3, $advance->collection_attempts);
    }

    public function test_closes_pay_cycles_once_payday_has_passed(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 10:00'));
        $employee = $this->enrolledEmployee();
        $advance = app(RequestAdvance::class)->handle($employee, Money::omr(50), Disclosure::factory()->create(), $employee->user);

        app(Clock::class)->travelTo(CarbonImmutable::parse('2026-10-26'));
        $this->artisan('advances:collect');

        $this->assertSame(PayCycleStatus::Closed, $advance->payCycle->fresh()->status);
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
