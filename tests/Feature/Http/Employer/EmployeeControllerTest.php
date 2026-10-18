<?php

namespace Tests\Feature\Http\Employer;

use App\Enums\EmploymentStatus;
use App\Enums\EnrolmentStatus;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use App\Notifications\EmployeeInvited;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_lists_only_this_employers_staff_filtered_by_search(): void
    {
        $hr = $this->hrUser();
        Employee::factory()->for($hr->employer)->create(['name' => 'Aisha Al Rawahi']);
        Employee::factory()->for($hr->employer)->create(['name' => 'Salim Al Harthy']);
        Employee::factory()->create(['name' => 'Aisha From Another Company']);

        $this->actingAs($hr)
            ->get(route('employer.employees.index', ['search' => 'Aisha']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('employer/employees/index')
                ->has('employees.data', 1)
                ->where('employees.data.0.name', 'Aisha Al Rawahi')
                ->missing('employees.data.0.civil_id'));
    }

    public function test_adding_an_employee_puts_them_on_the_roster_and_emails_an_invitation(): void
    {
        Notification::fake();
        $hr = $this->hrUser();

        $this->actingAs($hr)->post(route('employer.employees.store'), [
            'name' => 'Maryam Al Siyabi',
            'staff_no' => 'AN-3001',
            'civil_id' => '12345678',
            'email' => 'maryam@example.test',
            'net_salary' => '925.500',
            'employment_start_date' => '2024-01-15',
        ])->assertRedirect();

        $employee = $hr->employer->employees()->sole();
        $this->assertSame(925_500, $employee->net_salary_baisa->baisa);
        $this->assertSame(EnrolmentStatus::Invited, $employee->enrolment_status);
        $this->assertNotNull($employee->invitation_token);
        Notification::assertSentTo(
            new AnonymousNotifiable,
            EmployeeInvited::class,
            fn (EmployeeInvited $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'maryam@example.test',
        );
    }

    public function test_staff_numbers_must_be_unique_within_the_employer_only(): void
    {
        Notification::fake();
        $hr = $this->hrUser();
        Employee::factory()->for($hr->employer)->create(['staff_no' => 'AN-1']);
        Employee::factory()->create(['staff_no' => 'AN-2']);

        $payload = [
            'name' => 'New Person',
            'civil_id' => '87654321',
            'email' => 'new@example.test',
            'net_salary' => '500',
            'employment_start_date' => '2024-01-15',
        ];

        $this->actingAs($hr)
            ->post(route('employer.employees.store'), [...$payload, 'staff_no' => 'AN-1'])
            ->assertSessionHasErrors('staff_no');

        $this->actingAs($hr)
            ->post(route('employer.employees.store'), [...$payload, 'staff_no' => 'AN-2'])
            ->assertSessionHasNoErrors();
    }

    public function test_other_employers_staff_are_not_found(): void
    {
        $hr = $this->hrUser();
        $someoneElse = Employee::factory()->create();

        $this->actingAs($hr)->get(route('employer.employees.show', $someoneElse->id))->assertNotFound();
        $this->actingAs($hr)->put(route('employer.employees.update', $someoneElse->id), [])->assertNotFound();
        $this->actingAs($hr)->post(route('employer.employees.approve', $someoneElse->id))->assertNotFound();
    }

    public function test_changing_the_salary_confirms_it_and_is_audited(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-18 12:00'));
        $hr = $this->hrUser();
        $employee = Employee::factory()->for($hr->employer)->enrolled()->create(['salary_verified_at' => now()->subYear()]);

        $this->actingAs($hr)->put(route('employer.employees.update', $employee->id), [
            'name' => $employee->name,
            'email' => $employee->email,
            'net_salary' => '1100',
            'employment_start_date' => $employee->employment_start_date->toDateString(),
            'employment_status' => 'on_notice',
        ])->assertRedirect(route('employer.employees.show', $employee->id));

        $employee->refresh();
        $this->assertSame(1_100_000, $employee->net_salary_baisa->baisa);
        $this->assertSame(EmploymentStatus::OnNotice, $employee->employment_status);
        $this->assertSame('2026-10-18', $employee->salary_verified_at?->toDateString());
        $this->assertDatabaseHas('audit_logs', ['action' => 'employee.updated', 'actor_id' => $hr->id]);
    }

    public function test_hr_user_without_an_employer_is_forbidden(): void
    {
        $this->actingAs(User::factory()->employerAdmin()->create())
            ->get(route('employer.employees.index'))
            ->assertForbidden();
    }

    private function hrUser(): User
    {
        return User::factory()->employerAdmin()->for(Employer::factory())->create();
    }
}
