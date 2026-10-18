<?php

namespace App\Enums;

/**
 * The lifecycle of a salary advance. See canTransitionTo() for the allowed moves.
 */
enum AdvanceStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Disbursing = 'disbursing';
    case DisbursementFailed = 'disbursement_failed';
    case Disbursed = 'disbursed';
    case CollectionFailed = 'collection_failed';
    case Overdue = 'overdue';
    case Collected = 'collected';
    case WrittenOff = 'written_off';

    /**
     * The statuses this status may move to.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Requested => [self::Approved, self::Rejected, self::Cancelled],
            self::Approved => [self::Disbursing, self::Cancelled],
            self::Disbursing => [self::Disbursed, self::DisbursementFailed],
            self::Disbursed => [self::Collected, self::CollectionFailed],
            self::CollectionFailed => [self::Collected, self::CollectionFailed, self::Overdue],
            self::Overdue => [self::Collected, self::WrittenOff],
            self::Rejected, self::Cancelled, self::DisbursementFailed, self::Collected, self::WrittenOff => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * Statuses whose principal counts against the employee's limit for the cycle.
     *
     * @return list<self>
     */
    public static function drawnAgainstLimit(): array
    {
        return [self::Requested, self::Approved, self::Disbursing, self::Disbursed, self::CollectionFailed, self::Overdue, self::Collected];
    }

    /**
     * Statuses where money has been paid out and not yet recovered.
     *
     * @return list<self>
     */
    public static function outstanding(): array
    {
        return [self::Disbursed, self::CollectionFailed, self::Overdue];
    }

    /**
     * Statuses of an advance from an earlier cycle that block new advances.
     *
     * @return list<self>
     */
    public static function unpaid(): array
    {
        return [self::CollectionFailed, self::Overdue, self::WrittenOff];
    }
}
