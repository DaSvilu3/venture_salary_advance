<?php

namespace Tests\Feature\Jobs;

use App\Enums\AdvanceStatus;
use App\Enums\LedgerEntryStatus;
use App\Jobs\CollectAdvance;
use App\Models\Advance;
use App\Services\Fake\FakeScenarios;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CollectAdvanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_successful_debit_collects_the_total_due(): void
    {
        $advance = Advance::factory()->create();

        CollectAdvance::dispatchSync($advance->id);

        $advance->refresh();
        $this->assertSame(AdvanceStatus::Collected, $advance->status);
        $this->assertSame(1, $advance->collection_attempts);
        $this->assertSame(102_500, $advance->ledgerEntries()->sole()->amount_baisa->baisa);
    }

    public function test_failed_debit_is_retried_after_the_configured_delay(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-25 06:00'));
        $advance = Advance::factory()->create();
        app(FakeScenarios::class)->failNext(FakeScenarios::COLLECTION, $advance->employee);

        CollectAdvance::dispatchSync($advance->id);

        $advance->refresh();
        $this->assertSame(AdvanceStatus::CollectionFailed, $advance->status);
        $this->assertSame('Insufficient funds in the account.', $advance->failure_reason);
        $this->assertSame('2026-10-27', $advance->next_collection_attempt_on?->toDateString());
        $this->assertSame(LedgerEntryStatus::Failed, $advance->ledgerEntries()->sole()->status);
    }

    public function test_advance_becomes_overdue_when_the_last_attempt_fails(): void
    {
        $advance = Advance::factory()->inStatus(AdvanceStatus::CollectionFailed)->create(['collection_attempts' => 2]);
        app(FakeScenarios::class)->failNext(FakeScenarios::COLLECTION, $advance->employee);

        CollectAdvance::dispatchSync($advance->id);

        $advance->refresh();
        $this->assertSame(AdvanceStatus::Overdue, $advance->status);
        $this->assertSame(3, $advance->collection_attempts);
        $this->assertNull($advance->next_collection_attempt_on);
    }

    public function test_manual_retry_can_recover_an_overdue_advance(): void
    {
        $advance = Advance::factory()->inStatus(AdvanceStatus::Overdue)->create(['collection_attempts' => 3]);

        CollectAdvance::dispatchSync($advance->id);

        $this->assertSame(AdvanceStatus::Collected, $advance->fresh()->status);
    }

    public function test_failed_manual_retry_leaves_an_overdue_advance_overdue(): void
    {
        $advance = Advance::factory()->inStatus(AdvanceStatus::Overdue)->create(['collection_attempts' => 3]);
        app(FakeScenarios::class)->failNext(FakeScenarios::COLLECTION, $advance->employee);

        CollectAdvance::dispatchSync($advance->id);

        $advance->refresh();
        $this->assertSame(AdvanceStatus::Overdue, $advance->status);
        $this->assertSame(4, $advance->collection_attempts);
    }

    public function test_does_not_collect_an_advance_that_was_never_paid_out(): void
    {
        $advance = Advance::factory()->inStatus(AdvanceStatus::DisbursementFailed)->create();

        CollectAdvance::dispatchSync($advance->id);

        $this->assertSame(0, $advance->ledgerEntries()->count());
    }
}
