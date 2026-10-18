<?php

namespace App\Contracts;

use App\Models\Advance;
use App\Models\Employee;
use App\Support\GatewayResult;
use App\Support\Money;

/**
 * The licensed bank: registers direct-debit mandates (CBO Mandate Management System), pays advances
 * into the employee's account and collects them on payday.
 */
interface BankGateway
{
    public function providerName(): string;

    public function registerMandate(Employee $employee, Money $maxAmount): GatewayResult;

    public function disburse(Advance $advance): GatewayResult;

    public function collect(Advance $advance): GatewayResult;
}
