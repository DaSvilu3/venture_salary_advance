<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\AdvanceStatus;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Database\Factories\AdvanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A short-term amount drawn against salary, repaid by direct debit on the cycle's payday.
 * Change its status only through AdvanceLifecycle.
 *
 * @property int $id
 * @property string $reference
 * @property int $employee_id
 * @property int $employer_id
 * @property int $pay_cycle_id
 * @property int $mandate_id
 * @property int $bank_partner_id
 * @property int $funder_partner_id
 * @property int $disclosure_id
 * @property Money $amount_baisa
 * @property Money $fee_baisa
 * @property Money $total_due_baisa
 * @property CarbonImmutable $due_date
 * @property AdvanceStatus $status
 * @property CarbonImmutable $disclosure_accepted_at
 * @property CarbonImmutable $requested_at
 * @property CarbonImmutable|null $disbursed_at
 * @property CarbonImmutable|null $collected_at
 * @property int $collection_attempts
 * @property CarbonImmutable|null $next_collection_attempt_on
 * @property string|null $failure_reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Employee $employee
 * @property-read Employer $employer
 * @property-read PayCycle $payCycle
 * @property-read Disclosure $disclosure
 */
#[Fillable([
    'reference', 'employee_id', 'employer_id', 'pay_cycle_id', 'mandate_id', 'bank_partner_id', 'funder_partner_id',
    'disclosure_id', 'amount_baisa', 'fee_baisa', 'total_due_baisa', 'due_date', 'status', 'disclosure_accepted_at',
    'requested_at', 'disbursed_at', 'collected_at', 'collection_attempts', 'next_collection_attempt_on', 'failure_reason',
])]
class Advance extends Model
{
    /** @use HasFactory<AdvanceFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Employer, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /**
     * @return BelongsTo<PayCycle, $this>
     */
    public function payCycle(): BelongsTo
    {
        return $this->belongsTo(PayCycle::class);
    }

    /**
     * @return BelongsTo<Mandate, $this>
     */
    public function mandate(): BelongsTo
    {
        return $this->belongsTo(Mandate::class);
    }

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function bankPartner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'bank_partner_id');
    }

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function funderPartner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'funder_partner_id');
    }

    /**
     * @return BelongsTo<Disclosure, $this>
     */
    public function disclosure(): BelongsTo
    {
        return $this->belongsTo(Disclosure::class);
    }

    /**
     * @return HasMany<LedgerEntry, $this>
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'subject_id')->where('subject_type', $this->getMorphClass());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_baisa' => MoneyCast::class,
            'fee_baisa' => MoneyCast::class,
            'total_due_baisa' => MoneyCast::class,
            'due_date' => 'immutable_date',
            'status' => AdvanceStatus::class,
            'disclosure_accepted_at' => 'immutable_datetime',
            'requested_at' => 'immutable_datetime',
            'disbursed_at' => 'immutable_datetime',
            'collected_at' => 'immutable_datetime',
            'collection_attempts' => 'integer',
            'next_collection_attempt_on' => 'immutable_date',
        ];
    }
}
