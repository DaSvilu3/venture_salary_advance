<?php

namespace Database\Factories;

use App\Enums\AgreementStatus;
use App\Models\Employer;
use App\Models\EmployerAgreement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployerAgreement>
 */
class EmployerAgreementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employer_id' => Employer::factory(),
            'version' => config('salary_access.agreement_version'),
            'status' => AgreementStatus::Pending,
            'decided_at' => null,
            'reject_reason' => null,
            'signed_path' => null,
            'signed_name' => null,
            'signed_uploaded_at' => null,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AgreementStatus::Accepted,
            'decided_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'The fee is too high for our payroll.'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AgreementStatus::Rejected,
            'decided_at' => now(),
            'reject_reason' => $reason,
        ]);
    }
}
