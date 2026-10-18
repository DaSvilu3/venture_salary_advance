<?php

namespace Tests\Feature\Http\Admin;

use App\Enums\AdvanceStatus;
use App\Models\Advance;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdvanceActionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_cancelling_an_approved_advance_records_the_admin(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $advance = Advance::factory()->inStatus(AdvanceStatus::Approved)->create();

        $this->actingAs($admin)->post(route('admin.advances.cancel', $advance->reference), ['note' => 'Duplicate request']);

        $this->assertSame(AdvanceStatus::Cancelled, $advance->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'advance.cancelled', 'actor_id' => $admin->id]);
    }

    public function test_cannot_write_off_an_advance_that_is_not_overdue(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $advance = Advance::factory()->inStatus(AdvanceStatus::Disbursed)->create();

        $this->actingAs($admin)
            ->post(route('admin.advances.write-off', $advance->reference))
            ->assertSessionHasErrors('advance');

        $this->assertSame(AdvanceStatus::Disbursed, $advance->fresh()->status);
    }

    public function test_writing_off_an_overdue_advance(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $advance = Advance::factory()->inStatus(AdvanceStatus::Overdue)->create();

        $this->actingAs($admin)->post(route('admin.advances.write-off', $advance->reference));

        $this->assertSame(AdvanceStatus::WrittenOff, $advance->fresh()->status);
    }

    public function test_collect_now_recovers_an_overdue_advance(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $advance = Advance::factory()->inStatus(AdvanceStatus::Overdue)->create(['collection_attempts' => 3]);

        $this->actingAs($admin)->post(route('admin.advances.collect', $advance->reference));

        $this->assertSame(AdvanceStatus::Collected, $advance->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'advance.collected', 'actor_id' => $admin->id]);
    }

    public function test_detail_page_offers_only_the_actions_the_lifecycle_allows(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $advance = Advance::factory()->inStatus(AdvanceStatus::Overdue)->create();

        $this->actingAs($admin)
            ->get(route('admin.advances.show', $advance->reference))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('allowedActions.cancel', false)
                ->where('allowedActions.retry_collection', true)
                ->where('allowedActions.write_off', true));
    }
}
