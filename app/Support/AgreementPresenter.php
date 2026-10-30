<?php

namespace App\Support;

use App\Models\Employer;
use App\Models\EmployerAgreement;
use App\Models\ProgramTerms;

/**
 * The page data for the programme agreement, shared by the employer and platform portals.
 */
class AgreementPresenter
{
    /**
     * @return array{context: string, employer: array{id: int, name: string, cr_number: string}, bank: string|null, funder: string|null, terms: array{flat_fee: int, limit_ratio_bps: int, max_limit: int, min_advance: int, collection_max_attempts: int, collection_retry_days: int}, agreement: array{version: string, status: string, decided_at: string|null, reject_reason: string|null, signed: array{name: string, uploaded_at: string|null}|null}, generated_on: string}
     */
    public static function present(Employer $employer, EmployerAgreement $agreement, string $context, Clock $clock): array
    {
        $terms = ProgramTerms::forEmployer($employer);

        return [
            'context' => $context,
            'employer' => [
                'id' => $employer->id,
                'name' => $employer->name,
                'cr_number' => $employer->cr_number,
            ],
            'bank' => $employer->bankPartner?->name,
            'funder' => $employer->funderPartner?->name,
            'terms' => [
                'flat_fee' => $terms->flat_fee_baisa->baisa,
                'limit_ratio_bps' => $terms->limit_ratio_bps,
                'max_limit' => $terms->max_limit_baisa->baisa,
                'min_advance' => $terms->min_advance_baisa->baisa,
                'collection_max_attempts' => $terms->collection_max_attempts,
                'collection_retry_days' => $terms->collection_retry_days,
            ],
            'agreement' => [
                'version' => $agreement->version,
                'status' => $agreement->status->value,
                'decided_at' => $agreement->decided_at?->toIso8601String(),
                'reject_reason' => $agreement->reject_reason,
                'signed' => $agreement->signed_path === null ? null : [
                    'name' => $agreement->signed_name,
                    'uploaded_at' => $agreement->signed_uploaded_at?->toIso8601String(),
                ],
            ],
            'generated_on' => $clock->today()->toDateString(),
        ];
    }
}
