<?php

namespace App\Jobs;

use App\Contracts\BankGateway;
use App\Contracts\FunderGateway;
use App\Domain\Advances\AdvanceLifecycle;
use App\Enums\AdvanceStatus;
use App\Enums\LedgerEntryStatus;
use App\Enums\LedgerEntryType;
use App\Models\Advance;
use App\Models\AuditLog;
use App\Models\ProgramTerms;
use App\Models\User;
use App\Support\Clock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

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
        DB::transaction(function () use ($lifecycle, $bank, $funder, $clock): void {
            // Lock the row and re-read the status, so a second worker (a double run, a repeated
            // retry, or two queue workers) cannot debit the same advance a second time.
            $advance = Advance::query()->whereKey($this->advanceId)->lockForUpdate()->first();

            if ($advance === null || ! in_array($advance->status, AdvanceStatus::outstanding(), true)) {
                return;
            }

            $terms = ProgramTerms::forEmployer($advance->employer);
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

            $meta = ['reason' => $result->failureReason, 'attempt' => $attempts];

            // A retry that fails while the advance is already overdue keeps it overdue, but is still
            // recorded in the audit trail rather than changing the status silently.
            if ($advance->status === AdvanceStatus::Overdue) {
                $advance->update(['collection_attempts' => $attempts, 'failure_reason' => $result->failureReason]);
                AuditLog::record('advance.collection_failed', $advance, $actor, [...$meta, 'while' => 'overdue']);

                return;
            }

            if ($attempts >= $terms->collection_max_attempts) {
                $lifecycle->transition($advance, AdvanceStatus::Overdue, $actor, [
                    'collection_attempts' => $attempts,
                    'failure_reason' => $result->failureReason,
                ], $meta);

                return;
            }

            $lifecycle->transition($advance, AdvanceStatus::CollectionFailed, $actor, [
                'collection_attempts' => $attempts,
                'failure_reason' => $result->failureReason,
                'next_collection_attempt_on' => $clock->today()->addDays($terms->collection_retry_days),
            ], $meta);
        });
    }
}
