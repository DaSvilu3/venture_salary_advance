<?php

namespace App\Domain\Roster;

use App\Enums\EnrolmentStatus;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use App\Support\Clock;
use DomainException;

/**
 * HR's decision on an employee who has finished onboarding. Approval also confirms the roster salary.
 */
class ReviewEnrolment
{
    public function __construct(private readonly Clock $clock) {}

    public function approve(Employee $employee, User $actor): Employee
    {
        $this->ensurePending($employee);

        $employee->update([
            'enrolment_status' => EnrolmentStatus::Enrolled,
            'salary_verified_at' => $employee->salary_verified_at ?? $this->clock->now(),
        ]);

        AuditLog::record('employee.enrolment_approved', $employee, $actor);

        return $employee;
    }

    public function reject(Employee $employee, User $actor, ?string $reason = null): Employee
    {
        $this->ensurePending($employee);

        $employee->update(['enrolment_status' => EnrolmentStatus::Rejected]);

        AuditLog::record('employee.enrolment_rejected', $employee, $actor, array_filter(['reason' => $reason]));

        return $employee;
    }

    private function ensurePending(Employee $employee): void
    {
        if ($employee->enrolment_status !== EnrolmentStatus::PendingApproval) {
            throw new DomainException(__('Only enrolments waiting for approval can be reviewed.'));
        }
    }
}
