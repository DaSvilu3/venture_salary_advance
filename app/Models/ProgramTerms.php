<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\FeeModel;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Database\Factories\ProgramTermsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Limits, pricing and eligibility thresholds for advances. The row without an employer holds the
 * platform defaults; an employer row overrides them.
 *
 * @property int $id
 * @property int|null $employer_id
 * @property int $limit_ratio_bps
 * @property Money $max_limit_baisa
 * @property Money $min_advance_baisa
 * @property FeeModel $fee_model
 * @property Money $flat_fee_baisa
 * @property int $min_tenure_months
 * @property int $cutoff_days_before_payday
 * @property int $max_draws_per_cycle
 * @property int $collection_max_attempts
 * @property int $collection_retry_days
 * @property int $collection_offset_days
 * @property int $disbursement_sla_hours
 * @property bool $auto_approve
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Table('program_terms')]
#[Fillable([
    'employer_id', 'limit_ratio_bps', 'max_limit_baisa', 'min_advance_baisa', 'fee_model', 'flat_fee_baisa',
    'min_tenure_months', 'cutoff_days_before_payday', 'max_draws_per_cycle', 'collection_max_attempts',
    'collection_retry_days', 'collection_offset_days', 'disbursement_sla_hours', 'auto_approve',
])]
class ProgramTerms extends Model
{
    /** @use HasFactory<ProgramTermsFactory> */
    use HasFactory;

    /**
     * The terms that apply to an employer: its own override, else the platform defaults, else config.
     */
    public static function forEmployer(Employer $employer): self
    {
        return static::query()->where('employer_id', $employer->id)->first()
            ?? static::platformDefaults();
    }

    public static function platformDefaults(): self
    {
        return static::query()->whereNull('employer_id')->first()
            ?? new self(config('salary_access.default_terms'));
    }

    /**
     * @return BelongsTo<Employer, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'limit_ratio_bps' => 'integer',
            'max_limit_baisa' => MoneyCast::class,
            'min_advance_baisa' => MoneyCast::class,
            'fee_model' => FeeModel::class,
            'flat_fee_baisa' => MoneyCast::class,
            'min_tenure_months' => 'integer',
            'cutoff_days_before_payday' => 'integer',
            'max_draws_per_cycle' => 'integer',
            'collection_max_attempts' => 'integer',
            'collection_retry_days' => 'integer',
            'collection_offset_days' => 'integer',
            'disbursement_sla_hours' => 'integer',
            'auto_approve' => 'boolean',
        ];
    }
}
