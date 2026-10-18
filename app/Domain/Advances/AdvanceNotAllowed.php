<?php

namespace App\Domain\Advances;

use App\Enums\IneligibilityReason;
use App\Support\Money;
use DomainException;

/**
 * Raised when an advance request breaks a program rule. The message is shown to the employee.
 */
class AdvanceNotAllowed extends DomainException
{
    /**
     * @param  list<IneligibilityReason>  $reasons
     */
    public static function ineligible(array $reasons): self
    {
        return new self($reasons[0]->message());
    }

    public static function belowMinimum(Money $minimum): self
    {
        return new self(__('The minimum advance is :amount.', ['amount' => $minimum->format()]));
    }

    public static function aboveAvailable(Money $available): self
    {
        return new self(__('You can draw up to :amount this pay cycle.', ['amount' => $available->format()]));
    }

    public static function staleDisclosure(): self
    {
        return new self(__('The terms have been updated. Please review and accept them again.'));
    }
}
