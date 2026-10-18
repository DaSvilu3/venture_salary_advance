<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\EmploymentStatus;
use App\Enums\EnrolmentStatus;
use App\Enums\MandateStatus;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A person on an employer's payroll roster. Linked to a user account once they accept their invitation.
 *
 * @property int $id
 * @property int $employer_id
 * @property int|null $user_id
 * @property string $staff_no
 * @property string $civil_id
 * @property string $name
 * @property string|null $phone
 * @property string $email
 * @property Money $net_salary_baisa
 * @property CarbonImmutable $employment_start_date
 * @property EmploymentStatus $employment_status
 * @property CarbonImmutable|null $salary_verified_at
 * @property string|null $iban
 * @property EnrolmentStatus $enrolment_status
 * @property string|null $invitation_token
 * @property CarbonImmutable|null $invited_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Employer $employer
 * @property-read User|null $user
 * @property-read Mandate|null $activeMandate
 */
#[Fillable([
    'employer_id', 'user_id', 'staff_no', 'civil_id', 'name', 'phone', 'email', 'net_salary_baisa',
    'employment_start_date', 'employment_status', 'salary_verified_at', 'iban', 'enrolment_status',
    'invitation_token', 'invited_at',
])]
#[Hidden(['invitation_token', 'civil_id'])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Employer, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Mandate, $this>
     */
    public function mandates(): HasMany
    {
        return $this->hasMany(Mandate::class);
    }

    /**
     * @return HasOne<Mandate, $this>
     */
    public function activeMandate(): HasOne
    {
        return $this->hasOne(Mandate::class)->where('status', MandateStatus::Active)->latestOfMany('signed_at');
    }

    /**
     * @return HasMany<Advance, $this>
     */
    public function advances(): HasMany
    {
        return $this->hasMany(Advance::class);
    }

    public function isEnrolled(): bool
    {
        return $this->enrolment_status === EnrolmentStatus::Enrolled;
    }

    /**
     * The last four characters of the civil ID, safe to show in the interface.
     */
    public function maskedCivilId(): string
    {
        return '•••• '.substr($this->civil_id, -4);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'net_salary_baisa' => MoneyCast::class,
            'employment_start_date' => 'immutable_date',
            'employment_status' => EmploymentStatus::class,
            'enrolment_status' => EnrolmentStatus::class,
            'salary_verified_at' => 'immutable_datetime',
            'invited_at' => 'immutable_datetime',
        ];
    }
}
