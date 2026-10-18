<?php

namespace App\Domain\Onboarding;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use App\Support\Clock;
use Illuminate\Support\Facades\DB;

/**
 * Turns an invited roster entry into an employee account. The invitation link proves the email address.
 */
class AcceptInvitation
{
    public function __construct(private readonly Clock $clock) {}

    public function handle(Employee $employee, string $password): User
    {
        return DB::transaction(function () use ($employee, $password): User {
            $user = User::query()->create([
                'name' => $employee->name,
                'email' => $employee->email,
                'password' => $password,
                'role' => Role::Employee,
            ]);

            $user->forceFill(['email_verified_at' => $this->clock->now()])->save();

            $employee->update([
                'user_id' => $user->id,
                'invitation_token' => null,
            ]);

            AuditLog::record('employee.invitation_accepted', $employee, $user);

            return $user;
        });
    }
}
