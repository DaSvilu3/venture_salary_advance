<?php

namespace App\Http\Controllers\Employee;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends EmployeePortalController
{
    /**
     * The employee's employment, salary and bank details as the platform holds them.
     */
    public function __invoke(Request $request): Response
    {
        $employee = $this->employee($request);
        $mandate = $employee->activeMandate;

        return Inertia::render('app/profile', [
            'profile' => [
                'name' => $employee->name,
                'email' => $employee->email,
                'staff_no' => $employee->staff_no,
                'civil_id' => $employee->maskedCivilId(),
                'employer' => $employee->employer->name,
                'net_salary' => $employee->net_salary_baisa,
                'salary_verified' => $employee->salary_verified_at !== null,
                'iban' => $employee->iban,
                'bank' => $employee->employer->bankPartner?->name,
                'mandate' => $mandate === null ? null : [
                    'reference' => $mandate->reference,
                    'signed_at' => $mandate->signed_at?->toIso8601String(),
                ],
            ],
        ]);
    }
}
