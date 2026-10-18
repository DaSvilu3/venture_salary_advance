<?php

namespace App\Domain\Advances;

use App\Enums\AdvanceStatus;
use App\Models\Advance;
use LogicException;

class InvalidAdvanceTransition extends LogicException
{
    public static function between(Advance $advance, AdvanceStatus $to): self
    {
        return new self("Advance [{$advance->reference}] cannot move from [{$advance->status->value}] to [{$to->value}].");
    }
}
