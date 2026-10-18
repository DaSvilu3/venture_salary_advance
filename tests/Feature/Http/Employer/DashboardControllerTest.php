<?php

namespace Tests\Feature\Http\Employer;

use App\Domain\Payroll\PayCycleCalendar;
use App\Enums\AdvanceStatus;
use App\Models\Advance;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_shows_totals_for_this_employer_without_individual_advances(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 10:00'));
        $hr = User::factory()->employerAdmin()->for(Employer::factory()->paydayOn(25))->create();
        $cycle = app(PayCycleCalendar::class)->cycleEndingOn($hr->employer, CarbonImmutable::parse('2026-10-25'));

        $salim = Employee::factory()->for($hr->employer)->enrolled()->create();
        $aisha = Employee::factory()->for($hr->employer)->enrolled()->create();
        Employee::factory()->for($hr->employer)->pendingApproval()->create();
        Advance::factory()->for($salim)->for($cycle)->forAmount(Money::omr(100))->create();
        Advance::factory()->for($salim)->for($cycle)->forAmount(Money::omr(20))->create();
        Advance::factory()->for($aisha)->for($cycle)->forAmount(Money::omr(30))->inStatus(AdvanceStatus::DisbursementFailed)->create();
        Advance::factory()->forAmount(Money::omr(400))->create();

        $this->actingAs($hr)
            ->get(route('employer.dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('employer/dashboard')
                ->where('enrolment.enrolled', 2)
                ->where('enrolment.pending_approval', 1)
                ->where('cycle.drawn', 120_000)
                ->where('cycle.advances', 2)
                ->where('cycle.employees_using', 1)
                ->where('collections.outstanding', 125_000)
                ->has('pendingApprovals', 1)
                ->missing('advances'));
    }
}
