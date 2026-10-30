<?php

namespace App\Jobs;

use App\Contracts\BankGateway;
use App\Contracts\FunderGateway;
use App\Domain\Advances\AdvanceLifecycle;
use App\Domain\Advances\InvalidAdvanceTransition;
use App\Enums\AdvanceStatus;
use App\Enums\LedgerEntryStatus;
use App\Enums\LedgerEntryType;
use App\Models\Advance;
use App\Models\ProgramTerms;
use App\Support\Clock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Asks the funder for capital, then the bank to pay the advance into the employee's account.
 */
class DisburseAdvance implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $advanceId) {}

    public function handle(AdvanceLifecycle $lifecycle, FunderGateway $funder, BankGateway $bank, Clock $clock): void
    {
        $advance = Advance::query()->find($this->advanceId);

        if ($advance === null || $advance->status !== AdvanceStatus::Approved) {
            return;
        }

        try {
            // Claim the advance. If another worker already moved it out of Approved, stop here.
            $lifecycle->transition($advance, AdvanceStatus::Disbursing);
        } catch (InvalidAdvanceTransition) {
            return;
        }

        try {
            $funding = $funder->fund($advance);
        } catch (Throwable) {
            $this->markFailed($lifecycle, $advance, 'Funding could not be reached.', $funder->providerName());

            return;
        }

        if (! $funding->succeeded) {
            $this->markFailed($lifecycle, $advance, $funding->failureReason, $funder->providerName());

            return;
        }

        try {
            $transfer = $bank->disburse($advance);
        } catch (Throwable) {
            // The funder already released capital; reverse it so the receivable is not orphaned.
            $this->reverseFunding($funder, $advance, $clock);
            $this->markFailed($lifecycle, $advance, 'Bank transfer could not be reached.', $bank->providerName());

            return;
        }

        $advance->ledgerEntries()->create([
            'type' => LedgerEntryType::Disbursement,
            'status' => $transfer->succeeded ? LedgerEntryStatus::Succeeded : LedgerEntryStatus::Failed,
            'amount_baisa' => $advance->amount_baisa,
            'provider' => $bank->providerName(),
            'external_reference' => $transfer->reference,
            'failure_reason' => $transfer->failureReason,
            'occurred_at' => $clock->now(),
        ]);

        if (! $transfer->succeeded) {
            $this->reverseFunding($funder, $advance, $clock);
            $this->markFailed($lifecycle, $advance, $transfer->failureReason, $bank->providerName());

            return;
        }

        $terms = ProgramTerms::forEmployer($advance->employer);

        $lifecycle->transition($advance, AdvanceStatus::Disbursed, attributes: [
            'next_collection_attempt_on' => $advance->due_date->addDays($terms->collection_offset_days),
        ], meta: [
            'funding_reference' => $funding->reference,
            'transfer_reference' => $transfer->reference,
        ]);
    }

    /**
     * Last-resort recovery: if the job throws after claiming the advance, don't leave it stuck in
     * Disbursing. The queue calls this once the job has finally failed.
     */
    public function failed(?Throwable $exception): void
    {
        $advance = Advance::query()->find($this->advanceId);

        if ($advance !== null && $advance->status === AdvanceStatus::Disbursing) {
            app(AdvanceLifecycle::class)->transition($advance, AdvanceStatus::DisbursementFailed, attributes: [
                'failure_reason' => 'Disbursement did not complete.',
            ]);
        }
    }

    private function markFailed(AdvanceLifecycle $lifecycle, Advance $advance, ?string $reason, string $provider): void
    {
        $lifecycle->transition($advance, AdvanceStatus::DisbursementFailed, attributes: [
            'failure_reason' => $reason,
        ], meta: ['provider' => $provider]);
    }

    private function reverseFunding(FunderGateway $funder, Advance $advance, Clock $clock): void
    {
        $reversal = $funder->reverseFunding($advance);

        $advance->ledgerEntries()->create([
            'type' => LedgerEntryType::Reversal,
            'status' => $reversal->succeeded ? LedgerEntryStatus::Succeeded : LedgerEntryStatus::Failed,
            'amount_baisa' => $advance->amount_baisa,
            'provider' => $funder->providerName(),
            'external_reference' => $reversal->reference,
            'failure_reason' => $reversal->failureReason,
            'occurred_at' => $clock->now(),
        ]);
    }
}
