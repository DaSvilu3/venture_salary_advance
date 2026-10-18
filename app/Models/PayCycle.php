<?php

namespace App\Models;

use App\Enums\PayCycleStatus;
use Carbon\CarbonImmutable;
use Database\Factories\PayCycleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One salary period for an employer, ending on payday. Advances drawn in the cycle are due on its payday.
 *
 * @property int $id
 * @property int $employer_id
 * @property CarbonImmutable $period_start
 * @property CarbonImmutable $payday
 * @property PayCycleStatus $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Employer $employer
 */
#[Fillable(['employer_id', 'period_start', 'payday', 'status'])]
class PayCycle extends Model
{
    /** @use HasFactory<PayCycleFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Employer, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /**
     * @return HasMany<Advance, $this>
     */
    public function advances(): HasMany
    {
        return $this->hasMany(Advance::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'immutable_date',
            'payday' => 'immutable_date',
            'status' => PayCycleStatus::class,
        ];
    }
}
