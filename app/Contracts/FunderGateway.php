<?php

namespace App\Contracts;

use App\Models\Advance;
use App\Support\GatewayResult;

/**
 * The approved funder: provides the capital for each advance and holds the receivable.
 */
interface FunderGateway
{
    public function providerName(): string;

    public function fund(Advance $advance): GatewayResult;

    /**
     * Reverse funding already provided for an advance, e.g. when the bank transfer fails after the
     * funder has released the capital, so the receivable is not left orphaned.
     */
    public function reverseFunding(Advance $advance): GatewayResult;

    public function reportRepayment(Advance $advance): GatewayResult;
}
