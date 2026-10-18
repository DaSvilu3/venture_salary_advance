<?php

namespace App\Models;

use App\Enums\PartnerType;
use Carbon\CarbonImmutable;
use Database\Factories\PartnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A licensed bank (moves money, holds mandates) or an approved funder (provides capital).
 *
 * @property int $id
 * @property PartnerType $type
 * @property string $name
 * @property string $code
 * @property string|null $brand_color
 * @property string|null $logo_path
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['type', 'name', 'code', 'brand_color', 'logo_path'])]
class Partner extends Model
{
    /** @use HasFactory<PartnerFactory> */
    use HasFactory;

    /**
     * @return HasMany<Employer, $this>
     */
    public function bankedEmployers(): HasMany
    {
        return $this->hasMany(Employer::class, 'bank_partner_id');
    }

    /**
     * @return HasMany<Employer, $this>
     */
    public function fundedEmployers(): HasMany
    {
        return $this->hasMany(Employer::class, 'funder_partner_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PartnerType::class,
        ];
    }
}
