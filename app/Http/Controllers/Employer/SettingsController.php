<?php

namespace App\Http\Controllers\Employer;

use App\Models\AuditLog;
use App\Models\ProgramTerms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends EmployerPortalController
{
    public function edit(Request $request): Response
    {
        $employer = $this->employer($request);
        $terms = ProgramTerms::forEmployer($employer);

        return Inertia::render('employer/settings', [
            'employer' => [
                'name' => $employer->name,
                'cr_number' => $employer->cr_number,
                'status' => $employer->status->value,
                'payday_day' => $employer->payday_day,
                'subscription_plan' => $employer->subscription_plan,
                'bank' => $employer->bankPartner?->name,
                'funder' => $employer->funderPartner?->name,
            ],
            'terms' => [
                'limit_ratio_bps' => $terms->limit_ratio_bps,
                'max_limit' => $terms->max_limit_baisa,
                'min_advance' => $terms->min_advance_baisa,
                'flat_fee' => $terms->flat_fee_baisa,
                'min_tenure_months' => $terms->min_tenure_months,
                'cutoff_days_before_payday' => $terms->cutoff_days_before_payday,
                'max_draws_per_cycle' => $terms->max_draws_per_cycle,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $employer = $this->employer($request);
        $data = $request->validate([
            'payday_day' => ['required', 'integer', 'between:1,31'],
        ]);

        $employer->update($data);
        AuditLog::record('employer.payday_changed', $employer, $request->user(), $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payday saved.')]);

        return to_route('employer.settings.edit');
    }
}
