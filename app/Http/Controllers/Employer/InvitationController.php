<?php

namespace App\Http\Controllers\Employer;

use App\Domain\Roster\InviteEmployee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class InvitationController extends EmployerPortalController
{
    /**
     * Send a new invitation link. The previous link stops working.
     */
    public function store(Request $request, int $employee, InviteEmployee $invite): RedirectResponse
    {
        $employee = $this->rosterEmployee($request, $employee);

        if ($employee->user_id !== null) {
            throw ValidationException::withMessages(['invitation' => __('This employee already has an account.')]);
        }

        $invite->handle($employee, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent to :email.', ['email' => $employee->email])]);

        return back();
    }
}
