<?php

namespace App\Jobs;

use App\Contracts\BankGateway;
use App\Contracts\FunderGateway;
use App\Domain\Advances\AdvanceLifecycle;
use App\Enums\AdvanceStatus;
use App\Enums\LedgerEntryStatus;
use App\Enums\LedgerEntryType;
use App\Models\Advance;
use App\Models\User;
use App\Support\Clock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Collects the total due by direct debit. A failure is retried a few days later until the attempt limit,
 * after which the advance is overdue.
 */
class CollectAdvance implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $advanceId, public ?int $actorId = null) {}

    public function handle(AdvanceLifecycle $lifecycle, BankGateway $bank, FunderGateway $funder, Clock $clock): void
    {
        $advance = Advance::query()->find($this->advanceId);

        if ($advance === null || ! in_array($advance->status, AdvanceStatus::outstanding(), true)) {
            return;
        }

        $actor = $this->actorId === null ? null : User::query()->find($this->actorId);
        $attempts = $advance->collection_attempts + 1;
        $result = $bank->collect($advance);

        $advance->ledgerEntries()->create([
            'type' => LedgerEntryType::Collection,
            'status' => $result->succeeded ? LedgerEntryStatus::Succeeded : LedgerEntryStatus::Failed,
            'amount_baisa' => $advance->total_due_baisa,
            'provider' => $bank->providerName(),
            'external_reference' => $result->reference,
            'failure_reason' => $result->failureReason,
            'occurred_at' => $clock->now(),
        ]);

        if ($result->succeeded) {
            $lifecycle->transition($advance, AdvanceStatus::Collected, $actor, [
                'collection_attempts' => $attempts,
                'failure_reason' => null,
            ], ['reference' => $result->reference, 'attempt' => $attempts]);

            $funder->reportRepayment($advance);

            return;
        }

        if ($advance->status === AdvanceStatus::Overdue) {
            $advance->update(['collection_attempts' => $attempts, 'failure_reason' => $result->failureReason]);

            return;
        }

        $meta = ['reason' => $result->failureReason, 'attempt' => $attempts];

        if ($attempts >= (int) config('salary_access.collections.max_attempts')) {
            $lifecycle->transition($advance, AdvanceStatus::Overdue, $actor, [
                'collection_attempts' => $attempts,
                'failure_reason' => $result->failureReason,
            ], $meta);

            return;
        }

        $lifecycle->transition($advance, AdvanceStatus::CollectionFailed, $actor, [
            'collection_attempts' => $attempts,
            'failure_reason' => $result->failureReason,
            'next_collection_attempt_on' => $clock->today()->addDays((int) config('salary_access.collections.retry_after_days')),
        ], $meta);
    }
}
