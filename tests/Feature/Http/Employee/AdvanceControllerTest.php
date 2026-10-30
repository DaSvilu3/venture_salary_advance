<?php

namespace Tests\Feature\Http\Employee;

use App\Enums\AdvanceStatus;
use App\Models\Advance;
use App\Models\Disclosure;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProgramTerms;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdvanceControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-10 10:00'));
    }

    public function test_home_shows_the_available_limit_and_advances_in_progress(): void
    {
        $employee = $this->enrolledEmployee();
        Advance::factory()->for($employee)->forAmount(Money::omr(40))->create();

        $this->actingAs($employee->user)
            ->get(route('app.home'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('app/home')
                ->where('eligibility.eligible', true)
                ->where('eligibility.limit', 150_000)
                ->has('activeAdvances.data', 1));
    }

    public function test_request_page_shows_the_current_disclosure_in_the_users_language(): void
    {
        $employee = $this->enrolledEmployee();
        $employee->user->update(['locale' => 'ar']);
        $disclosure = Disclosure::factory()->create();

        $this->actingAs($employee->user)
            ->get(route('app.advances.create'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('app/advances/create')
                ->where('disclosure.id', $disclosure->id)
                ->where('disclosure.body', $disclosure->body_ar));
    }

    public function test_request_page_shows_the_funding_promise_for_the_employers_terms(): void
    {
        $employee = $this->enrolledEmployee();
        Disclosure::factory()->create();
        ProgramTerms::factory()->create(['employer_id' => $employee->employer_id, 'disbursement_sla_hours' => 12]);

        $this->actingAs($employee->user)
            ->get(route('app.advances.create'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('app/advances/create')
                ->where('sla_hours', 12));
    }

    public function test_confirming_an_advance_pays_it_out_and_opens_its_page(): void
    {
        $employee = $this->enrolledEmployee();
        $disclosure = Disclosure::factory()->create();

        $response = $this->actingAs($employee->user)->post(route('app.advances.store'), [
            'amount' => '120.500',
            'disclosure_id' => $disclosure->id,
            'accept_terms' => true,
        ]);

        $advance = $employee->advances()->sole();
        $response->assertRedirect(route('app.advances.show', $advance->reference));
        $this->assertSame(120_500, $advance->amount_baisa->baisa);
        $this->assertSame(AdvanceStatus::Disbursed, $advance->status);
    }

    public function test_amount_above_the_limit_is_returned_as_a_field_error(): void
    {
        $employee = $this->enrolledEmployee();

        $this->actingAs($employee->user)
            ->post(route('app.advances.store'), [
                'amount' => '200',
                'disclosure_id' => Disclosure::factory()->create()->id,
                'accept_terms' => true,
            ])
            ->assertSessionHasErrors(['amount' => 'You can draw up to OMR 150.000 this pay cycle.']);

        $this->assertSame(0, $employee->advances()->count());
    }

    public function test_terms_must_be_accepted(): void
    {
        $employee = $this->enrolledEmployee();

        $this->actingAs($employee->user)
            ->post(route('app.advances.store'), [
                'amount' => '50',
                'disclosure_id' => Disclosure::factory()->create()->id,
            ])
            ->assertSessionHasErrors(['accept_terms' => 'Please read and accept the terms.']);
    }

    public function test_employee_cannot_open_another_employees_advance(): void
    {
        $employee = $this->enrolledEmployee();
        $someoneElses = Advance::factory()->create();

        $this->actingAs($employee->user)
            ->get(route('app.advances.show', $someoneElses->reference))
            ->assertNotFound();
    }

    public function test_advance_page_lists_its_timeline(): void
    {
        $employee = $this->enrolledEmployee();
        $this->actingAs($employee->user)->post(route('app.advances.store'), [
            'amount' => '50',
            'disclosure_id' => Disclosure::factory()->create()->id,
            'accept_terms' => true,
        ]);
        $advance = $employee->advances()->sole();

        $this->actingAs($employee->user)
            ->get(route('app.advances.show', $advance->reference))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('app/advances/show')
                ->where('advance.amount', 50_000)
                ->where('timeline.0.status', 'requested')
                ->where('timeline.3.status', 'disbursed')
                ->has('transactions', 1));
    }

    public function test_user_without_a_roster_record_is_forbidden(): void
    {
        $this->actingAs(User::factory()->employee()->create())
            ->get(route('app.advances.index'))
            ->assertForbidden();
    }

    private function enrolledEmployee(): Employee
    {
        return Employee::factory()
            ->for(Employer::factory()->paydayOn(25))
            ->enrolled()
            ->earning(Money::omr(800))
            ->create(['iban' => 'OM600180000012345678901']);
    }
}
