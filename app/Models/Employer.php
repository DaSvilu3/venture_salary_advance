<?php

namespace App\Models;

use App\Enums\EmployerStatus;
use Carbon\CarbonImmutable;
use Database\Factories\EmployerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property string $cr_number
 * @property EmployerStatus $status
 * @property int $payday_day
 * @property int|null $bank_partner_id
 * @property int|null $funder_partner_id
 * @property string|null $subscription_plan
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Partner|null $bankPartner
 * @property-read Partner|null $funderPartner
 */
#[Fillable(['name', 'cr_number', 'status', 'payday_day', 'bank_partner_id', 'funder_partner_id', 'subscription_plan'])]
class Employer extends Model
{
    /** @use HasFactory<EmployerFactory> */
    use HasFactory;

    public function isActive(): bool
    {
        return $this->status === EmployerStatus::Active;
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
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * HR administrators who manage this employer.
     *
     * @return HasMany<User, $this>
     */
    public function admins(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<PayCycle, $this>
     */
    public function payCycles(): HasMany
    {
        return $this->hasMany(PayCycle::class);
    }

    /**
     * @return HasMany<Advance, $this>
     */
    public function advances(): HasMany
    {
        return $this->hasMany(Advance::class);
    }

    /**
     * Program terms that override the platform defaults for this employer.
     *
     * @return HasOne<ProgramTerms, $this>
     */
    public function programTerms(): HasOne
    {
        return $this->hasOne(ProgramTerms::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EmployerStatus::class,
            'payday_day' => 'integer',
        ];
    }
}
