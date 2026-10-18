<?php

namespace App\Contracts;

use App\Models\Employee;
use App\Support\GatewayResult;

/**
 * Confirms an employee's net salary against payroll records (for example WPS salary files).
 */
interface PayrollVerifier
{
    public function verifySalary(Employee $employee): GatewayResult;
}
