<?php

namespace App\Http\Controllers\Employee;

use App\Domain\Eligibility\EligibilityService;
use App\Http\Resources\AdvanceResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends EmployeePortalController
{
    /**
     * The employee's available limit, payday and advances in progress.
     */
    public function __invoke(Request $request, EligibilityService $eligibility): Response|RedirectResponse
    {
        $employee = $this->employee($request);

        if (! $this->hasFinishedOnboarding($employee)) {
            return to_route('app.onboarding');
        }

        $bank = $employee->employer->bankPartner;

        return Inertia::render('app/home', [
            'employer' => $employee->employer->name,
            'bank' => $bank === null ? null : ['name' => $bank->name, 'brand_color' => $bank->brand_color],
            'eligibility' => $eligibility->check($employee)->toArray(),
            'activeAdvances' => AdvanceResource::collection(
                $employee->advances()->whereIn('status', self::ACTIVE_STATUSES)->latest('requested_at')->get(),
            ),
        ]);
    }
}
