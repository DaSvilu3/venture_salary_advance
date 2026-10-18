<?php

namespace Tests\Feature\Jobs;

use App\Enums\AdvanceStatus;
use App\Enums\LedgerEntryStatus;
use App\Enums\LedgerEntryType;
use App\Jobs\DisburseAdvance;
use App\Models\Advance;
use App\Services\Fake\FakeScenarios;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DisburseAdvanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_pays_out_and_schedules_collection_on_the_due_date(): void
    {
        $advance = Advance::factory()->inStatus(AdvanceStatus::Approved)->create([
            'disbursed_at' => null,
            'next_collection_attempt_on' => null,
        ]);

        DisburseAdvance::dispatchSync($advance->id);

        $advance->refresh();
        $this->assertSame(AdvanceStatus::Disbursed, $advance->status);
        $this->assertNotNull($advance->disbursed_at);
        $this->assertSame($advance->due_date->toDateString(), $advance->next_collection_attempt_on?->toDateString());

        $entry = $advance->ledgerEntries()->sole();
        $this->assertSame(LedgerEntryType::Disbursement, $entry->type);
        $this->assertSame(LedgerEntryStatus::Succeeded, $entry->status);
        $this->assertSame(100_000, $entry->amount_baisa->baisa);
    }

    public function test_bank_rejection_fails_the_advance_and_records_the_attempt(): void
    {
        $advance = Advance::factory()->inStatus(AdvanceStatus::Approved)->create();
        app(FakeScenarios::class)->failNext(FakeScenarios::DISBURSEMENT, $advance->employee);

        DisburseAdvance::dispatchSync($advance->id);

        $advance->refresh();
        $this->assertSame(AdvanceStatus::DisbursementFailed, $advance->status);
        $this->assertSame('Beneficiary account rejected the credit.', $advance->failure_reason);
        $this->assertSame(LedgerEntryStatus::Failed, $advance->ledgerEntries()->sole()->status);
    }

    public function test_funding_refusal_fails_the_advance_before_any_payout(): void
    {
        $advance = Advance::factory()->inStatus(AdvanceStatus::Approved)->create();
        app(FakeScenarios::class)->failNext(FakeScenarios::FUNDING, $advance->employee);

        DisburseAdvance::dispatchSync($advance->id);

        $advance->refresh();
        $this->assertSame(AdvanceStatus::DisbursementFailed, $advance->status);
        $this->assertSame('Funding line unavailable.', $advance->failure_reason);
        $this->assertSame(0, $advance->ledgerEntries()->count());
    }

    public function test_does_nothing_for_an_advance_that_is_no_longer_approved(): void
    {
        $advance = Advance::factory()->inStatus(AdvanceStatus::Cancelled)->create();

        DisburseAdvance::dispatchSync($advance->id);

        $this->assertSame(AdvanceStatus::Cancelled, $advance->fresh()->status);
        $this->assertSame(0, $advance->ledgerEntries()->count());
    }
}
