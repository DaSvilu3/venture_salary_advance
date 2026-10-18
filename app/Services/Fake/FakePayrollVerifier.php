<?php

namespace App\Services\Fake;

use App\Contracts\PayrollVerifier;
use App\Models\Employee;
use App\Support\GatewayResult;
use Illuminate\Support\Str;

/**
 * Stands in for payroll verification. Accepts the roster salary unless a failure was forced.
 */
class FakePayrollVerifier implements PayrollVerifier
{
    public function __construct(private readonly FakeScenarios $scenarios) {}

    public function verifySalary(Employee $employee): GatewayResult
    {
        if ($this->scenarios->shouldFail(FakeScenarios::SALARY_VERIFICATION, $employee->id)) {
            return GatewayResult::failure('Salary does not match the latest payroll file.');
        }

        return GatewayResult::success('WPS-'.Str::upper(Str::random(10)));
    }
}
