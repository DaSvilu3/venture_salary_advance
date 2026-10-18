<?php

namespace App\Domain\Roster;

use App\Enums\EmploymentStatus;
use App\Enums\EnrolmentStatus;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use App\Support\Money;

/**
 * Adds a person to an employer's roster and invites them.
 */
class AddEmployee
{
    public function __construct(private readonly InviteEmployee $invite) {}

    /**
     * @param  array<string, mixed>  $data  Validated with RosterRowRules.
     */
    public function handle(Employer $employer, array $data, ?User $actor = null): Employee
    {
        $employee = $employer->employees()->create([
            'staff_no' => (string) $data['staff_no'],
            'name' => (string) $data['name'],
            'civil_id' => (string) $data['civil_id'],
            'email' => (string) $data['email'],
            'phone' => filled($data['phone'] ?? null) ? (string) $data['phone'] : null,
            'net_salary_baisa' => Money::omr((string) $data['net_salary']),
            'employment_start_date' => (string) $data['employment_start_date'],
            'employment_status' => EmploymentStatus::Active,
            'enrolment_status' => EnrolmentStatus::Invited,
        ]);

        AuditLog::record('employee.added', $employee, $actor);

        return $this->invite->handle($employee, $actor);
    }
}
