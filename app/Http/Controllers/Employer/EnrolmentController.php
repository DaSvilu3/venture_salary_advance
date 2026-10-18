<?php

namespace App\Http\Controllers\Employer;

use App\Domain\Roster\ReviewEnrolment;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EnrolmentController extends EmployerPortalController
{
    public function approve(Request $request, int $employee, ReviewEnrolment $review): RedirectResponse
    {
        $employee = $this->rosterEmployee($request, $employee);

        try {
            $review->approve($employee, $request->user());
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['enrolment' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name can now use salary advances.', ['name' => $employee->name])]);

        return back();
    }

    public function reject(Request $request, int $employee, ReviewEnrolment $review): RedirectResponse
    {
        $employee = $this->rosterEmployee($request, $employee);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        try {
            $review->reject($employee, $request->user(), $data['reason'] ?? null);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['enrolment' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'info', 'message' => __('Enrolment rejected.')]);

        return back();
    }
}
