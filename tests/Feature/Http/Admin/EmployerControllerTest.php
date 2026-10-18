<?php

namespace Tests\Feature\Http\Admin;

use App\Enums\EmployerStatus;
use App\Enums\Role;
use App\Models\Employer;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmployerControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_new_employers_start_pending(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $bank = Partner::factory()->bank()->create();
        $funder = Partner::factory()->funder()->create();

        $this->actingAs($admin)->post(route('admin.employers.store'), [
            'name' => 'Sur Fisheries LLC',
            'cr_number' => '1999999',
            'payday_day' => 27,
            'bank_partner_id' => $bank->id,
            'funder_partner_id' => $funder->id,
        ])->assertRedirect();

        $employer = Employer::query()->sole();
        $this->assertSame(EmployerStatus::Pending, $employer->status);
        $this->assertSame(27, $employer->payday_day);
    }

    public function test_bank_and_funder_must_be_partners_of_the_right_type(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $funder = Partner::factory()->funder()->create();

        $this->actingAs($admin)
            ->post(route('admin.employers.store'), [
                'name' => 'Sur Fisheries LLC',
                'cr_number' => '1999999',
                'payday_day' => 27,
                'bank_partner_id' => $funder->id,
                'funder_partner_id' => $funder->id,
            ])
            ->assertSessionHasErrors('bank_partner_id')
            ->assertSessionDoesntHaveErrors('funder_partner_id');
    }

    public function test_activating_an_employer_is_audited(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $employer = Employer::factory()->pending()->create();

        $this->actingAs($admin)->put(route('admin.employers.update', $employer), [
            'name' => $employer->name,
            'status' => 'active',
            'payday_day' => $employer->payday_day,
            'bank_partner_id' => $employer->bank_partner_id,
            'funder_partner_id' => $employer->funder_partner_id,
        ])->assertRedirect(route('admin.employers.show', $employer));

        $this->assertSame(EmployerStatus::Active, $employer->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employer.updated', 'actor_id' => $admin->id]);
    }

    public function test_inviting_an_hr_administrator_creates_a_scoped_account_and_sends_a_password_link(): void
    {
        Notification::fake();
        $admin = User::factory()->platformAdmin()->create();
        $employer = Employer::factory()->create();

        $this->actingAs($admin)->post(route('admin.employers.admins.store', $employer), [
            'name' => 'Huda Al Balushi',
            'email' => 'huda@example.test',
        ])->assertRedirect();

        $hr = User::query()->where('email', 'huda@example.test')->sole();
        $this->assertSame(Role::EmployerAdmin, $hr->role);
        $this->assertTrue($hr->employer->is($employer));
        $this->assertNotNull($hr->email_verified_at);
        Notification::assertSentTo($hr, ResetPassword::class);
    }

    public function test_inviting_a_partner_viewer_scopes_them_to_the_partner(): void
    {
        Notification::fake();
        $admin = User::factory()->platformAdmin()->create();
        $bank = Partner::factory()->bank()->create();

        $this->actingAs($admin)->post(route('admin.partners.viewers.store', $bank), [
            'name' => 'Bank Viewer',
            'email' => 'viewer@bank.test',
        ]);

        $viewer = User::query()->where('email', 'viewer@bank.test')->sole();
        $this->assertSame(Role::PartnerViewer, $viewer->role);
        $this->assertTrue($viewer->partner->is($bank));
    }
}
