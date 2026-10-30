<?php

namespace App\Models;

use App\Enums\AgreementStatus;
use Carbon\CarbonImmutable;
use Database\Factories\EmployerAgreementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The salary advance programme agreement for an employer. The platform generates it; the employer
 * accepts or rejects it, and may upload a signed copy kept on the private disk.
 *
 * @property int $id
 * @property int $employer_id
 * @property string $version
 * @property AgreementStatus $status
 * @property CarbonImmutable|null $decided_at
 * @property string|null $reject_reason
 * @property string|null $signed_path
 * @property string|null $signed_name
 * @property CarbonImmutable|null $signed_uploaded_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Employer $employer
 */
#[Fillable(['employer_id', 'version', 'status', 'decided_at', 'reject_reason', 'signed_path', 'signed_name', 'signed_uploaded_at'])]
class EmployerAgreement extends Model
{
    /** @use HasFactory<EmployerAgreementFactory> */
    use HasFactory;

    /**
     * The employer's agreement, created as pending on first use.
     */
    public static function forEmployer(Employer $employer): self
    {
        return static::query()->firstOrCreate(
            ['employer_id' => $employer->id],
            ['version' => config('salary_access.agreement_version'), 'status' => AgreementStatus::Pending],
        );
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
            'status' => AgreementStatus::class,
            'decided_at' => 'immutable_datetime',
            'signed_uploaded_at' => 'immutable_datetime',
        ];
    }
}
