<?php

namespace Tests\Feature\Http\Employee;

use App\Enums\EnrolmentStatus;
use App\Enums\MandateStatus;
use App\Models\Employee;
use App\Services\Fake\FakeScenarios;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OnboardingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_home_sends_employees_who_have_not_finished_onboarding_to_the_checklist(): void
    {
        $employee = Employee::factory()->withAccount()->create();

        $this->actingAs($employee->user)->get(route('app.home'))->assertRedirect(route('app.onboarding'));
    }

    public function test_saves_a_normalized_iban(): void
    {
        $employee = Employee::factory()->withAccount()->create();

        $this->actingAs($employee->user)
            ->put(route('app.onboarding.bank-account'), ['iban' => 'om60 0180 0000 1234 5678 901'])
            ->assertRedirect(route('app.onboarding'));

        $this->assertSame('OM600180000012345678901', $employee->fresh()->iban);
    }

    public function test_rejects_an_invalid_iban(): void
    {
        $employee = Employee::factory()->withAccount()->create();

        $this->actingAs($employee->user)
            ->put(route('app.onboarding.bank-account'), ['iban' => 'OM610180000012345678901'])
            ->assertSessionHasErrors('iban');

        $this->assertNull($employee->fresh()->iban);
    }

    public function test_changing_the_iban_cancels_the_existing_mandate(): void
    {
        $employee = Employee::factory()->enrolled()->create();

        $this->actingAs($employee->user)
            ->put(route('app.onboarding.bank-account'), ['iban' => 'OM600180000012345678901']);

        $this->assertNull($employee->fresh()->activeMandate);
        $this->assertSame(MandateStatus::Cancelled, $employee->mandates()->sole()->status);
    }

    public function test_signing_the_mandate_verifies_salary_and_sends_the_enrolment_to_hr(): void
    {
        $employee = Employee::factory()->withAccount()->create(['iban' => 'OM600180000012345678901']);

        $this->actingAs($employee->user)
            ->post(route('app.onboarding.mandate'))
            ->assertRedirect(route('app.onboarding'));

        $employee->refresh();
        $this->assertSame(EnrolmentStatus::PendingApproval, $employee->enrolment_status);
        $this->assertNotNull($employee->salary_verified_at);
        $this->assertSame($employee->employer->bank_partner_id, $employee->activeMandate->bank_partner_id);
    }

    public function test_signing_without_a_bank_account_is_refused(): void
    {
        $employee = Employee::factory()->withAccount()->create();

        $this->actingAs($employee->user)
            ->post(route('app.onboarding.mandate'))
            ->assertSessionHasErrors(['mandate' => 'Add your bank account first.']);

        $this->assertSame(0, $employee->mandates()->count());
    }

    public function test_salary_mismatch_leaves_the_salary_unverified(): void
    {
        $employee = Employee::factory()->withAccount()->create(['iban' => 'OM600180000012345678901']);
        app(FakeScenarios::class)->failNext(FakeScenarios::SALARY_VERIFICATION, $employee);

        $this->actingAs($employee->user)->post(route('app.onboarding.mandate'));

        $employee->refresh();
        $this->assertNull($employee->salary_verified_at);
        $this->assertSame(EnrolmentStatus::PendingApproval, $employee->enrolment_status);
    }
}
