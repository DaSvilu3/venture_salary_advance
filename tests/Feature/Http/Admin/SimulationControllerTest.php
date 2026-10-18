<?php

namespace Tests\Feature\Http\Admin;

use App\Enums\AdvanceStatus;
use App\Models\Advance;
use App\Models\User;
use App\Services\Fake\FakeScenarios;
use App\Support\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SimulationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_moving_the_clock_to_payday_and_running_collections_collects_due_advances(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 10:00'));
        $admin = User::factory()->platformAdmin()->create();
        $advance = Advance::factory()->create();
        $payday = $advance->due_date->toDateString();

        $this->actingAs($admin)->put(route('admin.simulation.clock.update'), ['date' => $payday]);
        $this->assertSame($payday, app(Clock::class)->today()->toDateString());

        $this->actingAs($admin)->post(route('admin.simulation.collections.run'))->assertRedirect(route('admin.simulation.show'));

        $this->assertSame(AdvanceStatus::Collected, $advance->fresh()->status);
    }

    public function test_forced_failures_are_listed_and_make_the_next_collection_fail(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $advance = Advance::factory()->create(['next_collection_attempt_on' => now()->subDay()]);

        $this->actingAs($admin)->post(route('admin.simulation.failures.store'), [
            'employee_id' => $advance->employee_id,
            'operation' => FakeScenarios::COLLECTION,
            'times' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.simulation.show'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('failures.0.operation', 'collection')
                ->where('failures.0.remaining', 2)
                ->where('failures.0.employee', $advance->employee->name));

        $this->actingAs($admin)->post(route('admin.simulation.collections.run'));

        $this->assertSame(AdvanceStatus::CollectionFailed, $advance->fresh()->status);
        $this->assertSame([['operation' => 'collection', 'employee_id' => $advance->employee_id, 'remaining' => 1]], app(FakeScenarios::class)->all());
    }

    public function test_resetting_the_clock_returns_to_the_real_date(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        app(Clock::class)->travelTo(CarbonImmutable::parse('2030-01-01'));

        $this->actingAs($admin)->delete(route('admin.simulation.clock.reset'));

        $this->assertFalse(app(Clock::class)->isSimulated());
    }
}
