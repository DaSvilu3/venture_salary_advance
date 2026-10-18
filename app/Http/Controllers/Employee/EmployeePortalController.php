<?php

namespace App\Http\Controllers\Employee;

use App\Enums\AdvanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

abstract class EmployeePortalController extends Controller
{
    /**
     * Statuses shown as "in progress" to the employee.
     *
     * @var list<AdvanceStatus>
     */
    protected const ACTIVE_STATUSES = [
        AdvanceStatus::Requested,
        AdvanceStatus::Approved,
        AdvanceStatus::Disbursing,
        AdvanceStatus::Disbursed,
        AdvanceStatus::CollectionFailed,
        AdvanceStatus::Overdue,
    ];

    /**
     * The roster record of the signed-in employee.
     */
    protected function employee(Request $request): Employee
    {
        $employee = $request->user()->employee;

        abort_if($employee === null, 403);

        return $employee;
    }

    protected function hasFinishedOnboarding(Employee $employee): bool
    {
        return $employee->iban !== null && $employee->activeMandate !== null;
    }
}
