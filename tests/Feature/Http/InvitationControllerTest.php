<?php

namespace Tests\Feature\Http;

use App\Enums\Role;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class InvitationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_shows_the_invitation_to_the_invited_employee(): void
    {
        $employee = Employee::factory()->create(['invitation_token' => 'invite-123', 'name' => 'Khalid Al Hinai']);

        $this->get(route('invitation.show', 'invite-123'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('auth/accept-invitation')
                ->where('name', 'Khalid Al Hinai')
                ->where('employer', $employee->employer->name)
                ->missing('civil_id'));
    }

    public function test_unknown_or_used_invitation_returns_404(): void
    {
        Employee::factory()->withAccount()->create(['invitation_token' => 'used-token']);

        $this->get(route('invitation.show', 'nope'))->assertNotFound();
        $this->get(route('invitation.show', 'used-token'))->assertNotFound();
    }

    public function test_accepting_creates_a_verified_employee_account_and_signs_in(): void
    {
        $employee = Employee::factory()->create(['invitation_token' => 'invite-123', 'civil_id' => '10234567']);

        $this->post(route('invitation.accept', 'invite-123'), [
            'civil_id_last4' => '4567',
            'password' => 'a-long-password',
            'password_confirmation' => 'a-long-password',
        ])->assertRedirect(route('app.onboarding'));

        $employee->refresh();
        $user = User::query()->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(Role::Employee, $user->role);
        $this->assertSame($employee->email, $user->email);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame($user->id, $employee->user_id);
        $this->assertNull($employee->invitation_token);
    }

    public function test_rejects_civil_id_digits_that_do_not_match(): void
    {
        Employee::factory()->create(['invitation_token' => 'invite-123', 'civil_id' => '10234567']);

        $this->from(route('invitation.show', 'invite-123'))
            ->post(route('invitation.accept', 'invite-123'), [
                'civil_id_last4' => '1111',
                'password' => 'a-long-password',
                'password_confirmation' => 'a-long-password',
            ])
            ->assertRedirect(route('invitation.show', 'invite-123'))
            ->assertSessionHasErrors(['civil_id_last4' => 'These digits do not match the civil ID your employer holds.']);

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }
}
