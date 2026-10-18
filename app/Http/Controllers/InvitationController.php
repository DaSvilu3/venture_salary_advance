<?php

namespace App\Http\Controllers;

use App\Domain\Onboarding\AcceptInvitation;
use App\Http\Requests\AcceptInvitationRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function show(string $token): Response
    {
        $employee = $this->invitedEmployee($token);

        return Inertia::render('auth/accept-invitation', [
            'token' => $token,
            'name' => $employee->name,
            'email' => $employee->email,
            'employer' => $employee->employer->name,
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function store(AcceptInvitationRequest $request, string $token, AcceptInvitation $acceptInvitation): RedirectResponse
    {
        $employee = $this->invitedEmployee($token);

        if (substr($employee->civil_id, -4) !== $request->string('civil_id_last4')->toString()) {
            return back()->withErrors(['civil_id_last4' => __('These digits do not match the civil ID your employer holds.')]);
        }

        $user = $acceptInvitation->handle($employee, $request->string('password')->toString());

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('app.onboarding');
    }

    private function invitedEmployee(string $token): Employee
    {
        return Employee::query()
            ->where('invitation_token', $token)
            ->whereNull('user_id')
            ->firstOrFail();
    }
}
