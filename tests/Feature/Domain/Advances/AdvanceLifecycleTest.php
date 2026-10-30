<?php

namespace Tests\Feature\Domain\Advances;

use App\Domain\Advances\AdvanceLifecycle;
use App\Domain\Advances\InvalidAdvanceTransition;
use App\Enums\AdvanceStatus;
use App\Models\Advance;
use App\Models\User;
use App\Support\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdvanceLifecycleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_collecting_stamps_the_simulated_date_and_records_the_actor(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:15'));
        $advance = Advance::factory()->inStatus(AdvanceStatus::Disbursed)->create();
        $admin = User::factory()->platformAdmin()->create();
        app(Clock::class)->travelTo(CarbonImmutable::parse('2026-10-25'));

        app(AdvanceLifecycle::class)->transition($advance, AdvanceStatus::Collected, $admin, meta: ['note' => 'manual']);

        $advance->refresh();
        $this->assertSame(AdvanceStatus::Collected, $advance->status);
        $this->assertSame('2026-10-25 09:15:00', $advance->collected_at->toDateTimeString());
        $this->assertNull($advance->next_collection_attempt_on);

        $log = $advance->auditLogs()->sole();
        $this->assertSame('advance.collected', $log->action);
        $this->assertTrue($log->actor->is($admin));
        $this->assertSame(['from' => 'disbursed', 'note' => 'manual'], $log->meta);
    }

    public function test_rejects_moves_outside_the_lifecycle_without_saving(): void
    {
        $advance = Advance::factory()->inStatus(AdvanceStatus::Collected)->create();

        try {
            app(AdvanceLifecycle::class)->transition($advance, AdvanceStatus::Disbursed);
            $this->fail('Expected an invalid transition.');
        } catch (InvalidAdvanceTransition) {
            $this->assertSame(AdvanceStatus::Collected, $advance->fresh()->status);
            $this->assertSame(0, $advance->auditLogs()->count());
        }
    }

    public function test_transition_honours_the_current_row_not_a_stale_copy(): void
    {
        $advance = Advance::factory()->inStatus(AdvanceStatus::Approved)->create();

        // Another worker has already moved the advance on while we held a stale copy.
        Advance::query()->whereKey($advance->getKey())->update(['status' => AdvanceStatus::Cancelled->value]);

        try {
            app(AdvanceLifecycle::class)->transition($advance, AdvanceStatus::Disbursing);
            $this->fail('Expected the stale transition to be rejected.');
        } catch (InvalidAdvanceTransition) {
            $this->assertSame(AdvanceStatus::Cancelled, $advance->fresh()->status);
            $this->assertSame(0, $advance->auditLogs()->count());
        }
    }
}
