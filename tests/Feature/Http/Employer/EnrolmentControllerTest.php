<?php

namespace Tests\Feature\Http\Employer;

use App\Enums\EnrolmentStatus;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use App\Notifications\EmployeeInvited;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EnrolmentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_approving_enrols_the_employee_and_confirms_their_salary(): void
    {
        $hr = $this->hrUser();
        $employee = Employee::factory()->for($hr->employer)->pendingApproval()->create(['salary_verified_at' => null]);

        $this->actingAs($hr)->post(route('employer.employees.approve', $employee->id))->assertRedirect();

        $employee->refresh();
        $this->assertSame(EnrolmentStatus::Enrolled, $employee->enrolment_status);
        $this->assertNotNull($employee->salary_verified_at);
    }

    public function test_rejecting_records_the_reason(): void
    {
        $hr = $this->hrUser();
        $employee = Employee::factory()->for($hr->employer)->pendingApproval()->create();

        $this->actingAs($hr)->post(route('employer.employees.reject', $employee->id), ['reason' => 'Contract ends this month']);

        $this->assertSame(EnrolmentStatus::Rejected, $employee->fresh()->enrolment_status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.enrolment_rejected',
            'meta' => json_encode(['reason' => 'Contract ends this month']),
        ]);
    }

    public function test_only_pending_enrolments_can_be_reviewed(): void
    {
        $hr = $this->hrUser();
        $employee = Employee::factory()->for($hr->employer)->create();

        $this->actingAs($hr)
            ->post(route('employer.employees.approve', $employee->id))
            ->assertSessionHasErrors(['enrolment' => 'Only enrolments waiting for approval can be reviewed.']);

        $this->assertSame(EnrolmentStatus::Invited, $employee->fresh()->enrolment_status);
    }

    public function test_resending_an_invitation_replaces_the_link(): void
    {
        Notification::fake();
        $hr = $this->hrUser();
        $employee = Employee::factory()->for($hr->employer)->create(['invitation_token' => 'old-token']);

        $this->actingAs($hr)->post(route('employer.employees.invitation', $employee->id))->assertRedirect();

        $this->assertNotSame('old-token', $employee->fresh()->invitation_token);
        Notification::assertSentTo(new AnonymousNotifiable, EmployeeInvited::class);
    }

    public function test_cannot_invite_someone_who_already_has_an_account(): void
    {
        Notification::fake();
        $hr = $this->hrUser();
        $employee = Employee::factory()->for($hr->employer)->enrolled()->create();

        $this->actingAs($hr)
            ->post(route('employer.employees.invitation', $employee->id))
            ->assertSessionHasErrors(['invitation' => 'This employee already has an account.']);

        Notification::assertNothingSent();
    }

    private function hrUser(): User
    {
        return User::factory()->employerAdmin()->for(Employer::factory())->create();
    }
}
