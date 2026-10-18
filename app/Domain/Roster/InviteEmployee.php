<?php

namespace App\Domain\Roster;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\EmployeeInvited;
use App\Support\Clock;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use LogicException;

/**
 * Issues a fresh invitation link to an employee who has no account yet, and emails it.
 */
class InviteEmployee
{
    public function __construct(private readonly Clock $clock) {}

    public function handle(Employee $employee, ?User $actor = null): Employee
    {
        if ($employee->user_id !== null) {
            throw new LogicException("Employee [{$employee->id}] already has an account.");
        }

        $employee->update([
            'invitation_token' => Str::random(40),
            'invited_at' => $this->clock->now(),
        ]);

        Notification::route('mail', $employee->email)->notify(new EmployeeInvited($employee));

        AuditLog::record('employee.invited', $employee, $actor);

        return $employee;
    }
}
