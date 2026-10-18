<?php

namespace App\Services\Fake;

use App\Contracts\FunderGateway;
use App\Models\Advance;
use App\Support\GatewayResult;
use Illuminate\Support\Str;

/**
 * Stands in for the funder. Approves funding unless the simulation console forced a failure.
 */
class FakeFunderGateway implements FunderGateway
{
    public function __construct(private readonly FakeScenarios $scenarios) {}

    public function providerName(): string
    {
        return 'fake-funder';
    }

    public function fund(Advance $advance): GatewayResult
    {
        if ($this->scenarios->shouldFail(FakeScenarios::FUNDING, $advance->employee_id)) {
            return GatewayResult::failure('Funding line unavailable.');
        }

        return GatewayResult::success('FND-'.Str::upper(Str::random(10)));
    }

    public function reportRepayment(Advance $advance): GatewayResult
    {
        return GatewayResult::success('RPY-'.Str::upper(Str::random(10)));
    }
}
