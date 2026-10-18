<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\LedgerEntryStatus;
use App\Enums\LedgerEntryType;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Database\Factories\LedgerEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record of one money movement attempt reported by a bank or funder gateway.
 *
 * @property int $id
 * @property int $advance_id
 * @property LedgerEntryType $type
 * @property LedgerEntryStatus $status
 * @property Money $amount_baisa
 * @property string $provider
 * @property string|null $external_reference
 * @property string|null $failure_reason
 * @property CarbonImmutable $occurred_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['advance_id', 'type', 'status', 'amount_baisa', 'provider', 'external_reference', 'failure_reason', 'occurred_at'])]
class LedgerEntry extends Model
{
    /** @use HasFactory<LedgerEntryFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Advance, $this>
     */
    public function advance(): BelongsTo
    {
        return $this->belongsTo(Advance::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LedgerEntryType::class,
            'status' => LedgerEntryStatus::class,
            'amount_baisa' => MoneyCast::class,
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
