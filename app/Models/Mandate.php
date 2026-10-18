<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\MandateStatus;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Database\Factories\MandateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The employee's authorisation for the bank to collect advances from their account by direct debit.
 *
 * @property int $id
 * @property int $employee_id
 * @property int $bank_partner_id
 * @property string $reference
 * @property Money $max_amount_baisa
 * @property MandateStatus $status
 * @property CarbonImmutable|null $signed_at
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['employee_id', 'bank_partner_id', 'reference', 'max_amount_baisa', 'status', 'signed_at', 'cancelled_at'])]
class Mandate extends Model
{
    /** @use HasFactory<MandateFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function bankPartner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'bank_partner_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_amount_baisa' => MoneyCast::class,
            'status' => MandateStatus::class,
            'signed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }
}
