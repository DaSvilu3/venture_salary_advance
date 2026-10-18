<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FeeModel;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employer;
use App\Models\ProgramTerms;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Platform default terms, and per-employer overrides.
 */
class ProgramTermsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/terms', [
            'terms' => self::present(ProgramTerms::platformDefaults()),
            'overrides' => ProgramTerms::query()
                ->whereNotNull('employer_id')
                ->with('employer:id,name')
                ->get()
                ->map(fn (ProgramTerms $terms): array => ['employer_id' => (int) $terms->employer_id, 'employer' => $terms->employer?->name, ...self::present($terms)]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $terms = ProgramTerms::query()->whereNull('employer_id')->first() ?? new ProgramTerms;
        $terms->fill($this->validated($request))->save();
        AuditLog::record('terms.updated', $terms, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Program terms saved.')]);

        return to_route('admin.terms.edit');
    }

    public function updateForEmployer(Request $request, Employer $employer): RedirectResponse
    {
        $terms = $employer->programTerms ?? new ProgramTerms(['employer_id' => $employer->id]);
        $terms->fill($this->validated($request))->save();
        AuditLog::record('terms.employer_override_saved', $employer, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Terms for :employer saved.', ['employer' => $employer->name])]);

        return to_route('admin.employers.show', $employer);
    }

    public function destroyForEmployer(Request $request, Employer $employer): RedirectResponse
    {
        $employer->programTerms()->delete();
        AuditLog::record('terms.employer_override_removed', $employer, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':employer now uses the platform terms.', ['employer' => $employer->name])]);

        return to_route('admin.employers.show', $employer);
    }

    /**
     * Terms as shown and edited in forms: rials as strings, ratio as a percentage.
     *
     * @return array<string, int|string>
     */
    public static function present(ProgramTerms $terms): array
    {
        return [
            'limit_ratio_percent' => (string) ($terms->limit_ratio_bps / 100),
            'max_limit' => $terms->max_limit_baisa->toOmrString(),
            'min_advance' => $terms->min_advance_baisa->toOmrString(),
            'flat_fee' => $terms->flat_fee_baisa->toOmrString(),
            'min_tenure_months' => $terms->min_tenure_months,
            'cutoff_days_before_payday' => $terms->cutoff_days_before_payday,
            'max_draws_per_cycle' => $terms->max_draws_per_cycle,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'limit_ratio_percent' => ['required', 'decimal:0,2', 'gt:0', 'max:100'],
            'max_limit' => ['required', 'decimal:0,3', 'gt:0'],
            'min_advance' => ['required', 'decimal:0,3', 'gt:0', 'lte:max_limit'],
            'flat_fee' => ['required', 'decimal:0,3', 'gte:0'],
            'min_tenure_months' => ['required', 'integer', 'between:0,24'],
            'cutoff_days_before_payday' => ['required', 'integer', 'between:0,15'],
            'max_draws_per_cycle' => ['required', 'integer', 'between:1,10'],
        ]);

        return [
            'limit_ratio_bps' => (int) round((float) $data['limit_ratio_percent'] * 100),
            'max_limit_baisa' => Money::omr((string) $data['max_limit']),
            'min_advance_baisa' => Money::omr((string) $data['min_advance']),
            'fee_model' => FeeModel::Flat,
            'flat_fee_baisa' => Money::omr((string) $data['flat_fee']),
            'min_tenure_months' => (int) $data['min_tenure_months'],
            'cutoff_days_before_payday' => (int) $data['cutoff_days_before_payday'],
            'max_draws_per_cycle' => (int) $data['max_draws_per_cycle'],
        ];
    }
}
