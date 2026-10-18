<?php

namespace App\Services\Fake;

use App\Contracts\BankGateway;
use App\Models\Advance;
use App\Models\Employee;
use App\Support\GatewayResult;
use App\Support\Money;
use Illuminate\Support\Str;

/**
 * Stands in for the bank. Succeeds unless the simulation console forced a failure. No money moves.
 */
class FakeBankGateway implements BankGateway
{
    public function __construct(private readonly FakeScenarios $scenarios) {}

    public function providerName(): string
    {
        return 'fake-bank';
    }

    public function registerMandate(Employee $employee, Money $maxAmount): GatewayResult
    {
        return GatewayResult::success('MND-'.Str::upper(Str::random(10)));
    }

    public function disburse(Advance $advance): GatewayResult
    {
        if ($this->scenarios->shouldFail(FakeScenarios::DISBURSEMENT, $advance->employee_id)) {
            return GatewayResult::failure('Beneficiary account rejected the credit.', $this->reference('ACH'));
        }

        return GatewayResult::success($this->reference('ACH'));
    }

    public function collect(Advance $advance): GatewayResult
    {
        if ($this->scenarios->shouldFail(FakeScenarios::COLLECTION, $advance->employee_id)) {
            return GatewayResult::failure('Insufficient funds in the account.', $this->reference('DD'));
        }

        return GatewayResult::success($this->reference('DD'));
    }

    private function reference(string $prefix): string
    {
        return $prefix.'-'.Str::upper(Str::random(12));
    }
}
