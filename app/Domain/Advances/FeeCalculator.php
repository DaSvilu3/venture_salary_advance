<?php

namespace App\Domain\Advances;

use App\Enums\FeeModel;
use App\Models\ProgramTerms;
use App\Support\Money;

class FeeCalculator
{
    /**
     * The fee charged on an advance of the given amount under the given terms.
     */
    public function feeFor(Money $amount, ProgramTerms $terms): Money
    {
        return match ($terms->fee_model) {
            FeeModel::Flat => $terms->flat_fee_baisa,
        };
    }
}
