<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdvanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Advance;
use App\Models\Disclosure;
use App\Models\ProgramTerms;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A read-only overview of the advance workflow: its steps, the platform rules in force, and a health check.
 */
class WorkflowController extends Controller
{
    public function __invoke(): Response
    {
        $terms = ProgramTerms::platformDefaults();
        $disclosure = Disclosure::current();

        return Inertia::render('admin/workflow', [
            'terms' => [
                'auto_approve' => $terms->auto_approve,
                'disbursement_sla_hours' => $terms->disbursement_sla_hours,
                'collection_offset_days' => $terms->collection_offset_days,
                'collection_max_attempts' => $terms->collection_max_attempts,
                'collection_retry_days' => $terms->collection_retry_days,
                'cutoff_days_before_payday' => $terms->cutoff_days_before_payday,
                'limit_ratio_bps' => $terms->limit_ratio_bps,
                'flat_fee' => $terms->flat_fee_baisa->baisa,
                'max_draws_per_cycle' => $terms->max_draws_per_cycle,
                'min_tenure_months' => $terms->min_tenure_months,
            ],
            'counts' => [
                'requested' => $this->countWithStatus(AdvanceStatus::Requested),
                'disbursement_failed' => $this->countWithStatus(AdvanceStatus::DisbursementFailed),
                'collection_failed' => $this->countWithStatus(AdvanceStatus::CollectionFailed),
                'overdue' => $this->countWithStatus(AdvanceStatus::Overdue),
                'disbursed' => $this->countWithStatus(AdvanceStatus::Disbursed),
            ],
            'disclosure' => $disclosure === null ? null : [
                'version' => $disclosure->version,
                'published_at' => $disclosure->published_at?->toIso8601String(),
            ],
            'editableTerms' => ProgramTermsController::present($terms),
            'drivers' => config('salary_access.drivers'),
            'employerOverrides' => ProgramTerms::query()->whereNotNull('employer_id')->count(),
        ]);
    }

    private function countWithStatus(AdvanceStatus $status): int
    {
        return Advance::query()->where('status', $status)->count();
    }
}
