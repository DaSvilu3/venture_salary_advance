<?php

namespace Tests\Unit\Enums;

use App\Enums\AdvanceStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdvanceStatusTest extends TestCase
{
    /**
     * @return array<string, array{AdvanceStatus, AdvanceStatus}>
     */
    public static function allowedTransitions(): array
    {
        return [
            'auto-approval' => [AdvanceStatus::Requested, AdvanceStatus::Approved],
            'rejection' => [AdvanceStatus::Requested, AdvanceStatus::Rejected],
            'payout starts' => [AdvanceStatus::Approved, AdvanceStatus::Disbursing],
            'payout succeeds' => [AdvanceStatus::Disbursing, AdvanceStatus::Disbursed],
            'payout fails' => [AdvanceStatus::Disbursing, AdvanceStatus::DisbursementFailed],
            'collected on payday' => [AdvanceStatus::Disbursed, AdvanceStatus::Collected],
            'first debit fails' => [AdvanceStatus::Disbursed, AdvanceStatus::CollectionFailed],
            'retry fails again' => [AdvanceStatus::CollectionFailed, AdvanceStatus::CollectionFailed],
            'retry succeeds' => [AdvanceStatus::CollectionFailed, AdvanceStatus::Collected],
            'retries exhausted' => [AdvanceStatus::CollectionFailed, AdvanceStatus::Overdue],
            'overdue recovered' => [AdvanceStatus::Overdue, AdvanceStatus::Collected],
            'overdue written off' => [AdvanceStatus::Overdue, AdvanceStatus::WrittenOff],
        ];
    }

    /**
     * @return array<string, array{AdvanceStatus, AdvanceStatus}>
     */
    public static function forbiddenTransitions(): array
    {
        return [
            'skip approval' => [AdvanceStatus::Requested, AdvanceStatus::Disbursed],
            'collect before payout' => [AdvanceStatus::Approved, AdvanceStatus::Collected],
            'cancel after payout' => [AdvanceStatus::Disbursed, AdvanceStatus::Cancelled],
            'reopen collected' => [AdvanceStatus::Collected, AdvanceStatus::Disbursed],
            'write off before overdue' => [AdvanceStatus::Disbursed, AdvanceStatus::WrittenOff],
            'retry failed payout' => [AdvanceStatus::DisbursementFailed, AdvanceStatus::Disbursing],
        ];
    }

    #[DataProvider('allowedTransitions')]
    public function test_allows_lifecycle_moves(AdvanceStatus $from, AdvanceStatus $to): void
    {
        $this->assertTrue($from->canTransitionTo($to));
    }

    #[DataProvider('forbiddenTransitions')]
    public function test_forbids_moves_outside_the_lifecycle(AdvanceStatus $from, AdvanceStatus $to): void
    {
        $this->assertFalse($from->canTransitionTo($to));
    }

    public function test_only_end_states_are_final(): void
    {
        $finals = array_values(array_filter(AdvanceStatus::cases(), fn (AdvanceStatus $status): bool => $status->isFinal()));

        $this->assertSame([
            AdvanceStatus::Rejected,
            AdvanceStatus::Cancelled,
            AdvanceStatus::DisbursementFailed,
            AdvanceStatus::Collected,
            AdvanceStatus::WrittenOff,
        ], $finals);
    }
}
