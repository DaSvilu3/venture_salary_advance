<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Employer;
use Illuminate\Http\Request;

abstract class EmployerPortalController extends Controller
{
    /**
     * The employer the signed-in HR administrator manages.
     */
    protected function employer(Request $request): Employer
    {
        $employer = $request->user()->employer;

        abort_if($employer === null, 403);

        return $employer;
    }

    /**
     * An employee on this employer's roster. Other employers' staff are reported as not found.
     */
    protected function rosterEmployee(Request $request, int $employeeId): Employee
    {
        return $this->employer($request)->employees()->findOrFail($employeeId);
    }
}
