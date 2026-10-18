<?php

namespace App\Jobs;

use App\Contracts\BankGateway;
use App\Contracts\FunderGateway;
use App\Domain\Advances\AdvanceLifecycle;
use App\Enums\AdvanceStatus;
use App\Enums\LedgerEntryStatus;
use App\Enums\LedgerEntryType;
use App\Models\Advance;
use App\Support\Clock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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

        $lifecycle->transition($advance, AdvanceStatus::Disbursing);

        $funding = $funder->fund($advance);

        if (! $funding->succeeded) {
            $lifecycle->transition($advance, AdvanceStatus::DisbursementFailed, attributes: [
                'failure_reason' => $funding->failureReason,
            ], meta: ['provider' => $funder->providerName()]);

            return;
        }

        $transfer = $bank->disburse($advance);

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
            $lifecycle->transition($advance, AdvanceStatus::DisbursementFailed, attributes: [
                'failure_reason' => $transfer->failureReason,
            ], meta: ['provider' => $bank->providerName()]);

            return;
        }

        $lifecycle->transition($advance, AdvanceStatus::Disbursed, attributes: [
            'next_collection_attempt_on' => $advance->due_date,
        ], meta: [
            'funding_reference' => $funding->reference,
            'transfer_reference' => $transfer->reference,
        ]);
    }
}
