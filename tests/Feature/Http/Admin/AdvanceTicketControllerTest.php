<?php

namespace Tests\Feature\Http\Admin;

use App\Models\Advance;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdvanceTicketControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_platform_admin_can_view_the_advance_ticket(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $advance = Advance::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.advances.ticket', $advance->reference))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/advances/ticket')
                ->where('advance.reference', $advance->reference)
                ->where('employer.name', $advance->employer->name)
                ->where('disclosure.version', $advance->disclosure->version)
                ->has('terms.limit')
                ->has('bank')
                ->has('timeline'));
    }

    public function test_the_ticket_masks_personal_identifiers(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $advance = Advance::factory()->create();
        $advance->employee->update([
            'civil_id' => '12345678',
            'iban' => 'OM1234567890123456789',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.advances.ticket', $advance->reference));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('employee.masked_civil_id', '•••• 5678')
            ->where('employee.masked_iban', 'OM•••••••••••••••6789'));

        $response->assertDontSee('12345678');
        $response->assertDontSee('OM1234567890123456789');
    }

    public function test_a_non_admin_cannot_view_the_ticket(): void
    {
        $advance = Advance::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.advances.ticket', $advance->reference))
            ->assertForbidden();
    }
}
