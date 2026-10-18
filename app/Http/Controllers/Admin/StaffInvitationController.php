<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Platform\InviteStaffUser;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Invites HR administrators for an employer and read-only viewers for a bank or funder.
 */
class StaffInvitationController extends Controller
{
    public function storeEmployerAdmin(Request $request, Employer $employer, InviteStaffUser $invite): RedirectResponse
    {
        $data = $this->validateInvitee($request);
        $invite->handle($data['name'], $data['email'], Role::EmployerAdmin, ['employer_id' => $employer->id], $request->user());

        return $this->invited($data['email']);
    }

    public function storePartnerViewer(Request $request, Partner $partner, InviteStaffUser $invite): RedirectResponse
    {
        $data = $this->validateInvitee($request);
        $invite->handle($data['name'], $data['email'], Role::PartnerViewer, ['partner_id' => $partner->id], $request->user());

        return $this->invited($data['email']);
    }

    /**
     * @return array{name: string, email: string}
     */
    private function validateInvitee(Request $request): array
    {
        /** @var array{name: string, email: string} */
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);
    }

    private function invited(string $email): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent to :email.', ['email' => $email])]);

        return back();
    }
}
