<?php

namespace App\Domain\Platform;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Clock;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Creates an HR administrator or partner viewer account and emails a link to choose a password,
 * reusing the password-reset flow.
 */
class InviteStaffUser
{
    public function __construct(private readonly Clock $clock) {}

    /**
     * @param  array{employer_id?: int, partner_id?: int}  $scope
     */
    public function handle(string $name, string $email, Role $role, array $scope, User $actor): User
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Str::random(40),
            'role' => $role,
            ...$scope,
        ]);

        $user->forceFill(['email_verified_at' => $this->clock->now()])->save();

        Password::broker()->sendResetLink(['email' => $user->email]);

        AuditLog::record('user.invited', $user, $actor, ['role' => $role->value, ...$scope]);

        return $user;
    }
}
