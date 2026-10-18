<?php

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Enums\EnrolmentStatus;
use App\Enums\MandateStatus;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Mandate;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * An invited employee who has not yet accepted. Use enrolled() for one who can take advances.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employer_id' => Employer::factory(),
            'user_id' => null,
            'staff_no' => fake()->unique()->bothify('EMP-#####'),
            'civil_id' => fake()->unique()->numerify('########'),
            'name' => fake()->name(),
            'phone' => fake()->numerify('+968 9#######'),
            'email' => fake()->unique()->safeEmail(),
            'net_salary_baisa' => Money::omr(800),
            'employment_start_date' => now()->subYears(2)->toDateString(),
            'employment_status' => EmploymentStatus::Active,
            'salary_verified_at' => null,
            'iban' => null,
            'enrolment_status' => EnrolmentStatus::Invited,
            'invitation_token' => Str::random(40),
            'invited_at' => now(),
        ];
    }

    /**
     * Has finished onboarding (account, IBAN, mandate, payroll match) and is waiting for HR approval.
     */
    public function pendingApproval(): static
    {
        return $this->withAccount()
            ->state(fn (array $attributes) => [
                'enrolment_status' => EnrolmentStatus::PendingApproval,
                'salary_verified_at' => now(),
                'iban' => fake()->numerify('OM##0##################'),
                'invitation_token' => null,
            ])
            ->afterCreating(function (Employee $employee): void {
                Mandate::factory()->for($employee)->create([
                    'bank_partner_id' => $employee->employer->bank_partner_id,
                    'status' => MandateStatus::Active,
                ]);
            });
    }

    /**
     * Fully onboarded: account, verified salary, IBAN and an active direct-debit mandate.
     */
    public function enrolled(): static
    {
        return $this->withAccount()
            ->state(fn (array $attributes) => [
                'enrolment_status' => EnrolmentStatus::Enrolled,
                'salary_verified_at' => now(),
                'iban' => fake()->numerify('OM##0##################'),
                'invitation_token' => null,
            ])
            ->afterCreating(function (Employee $employee): void {
                Mandate::factory()->for($employee)->create([
                    'bank_partner_id' => $employee->employer->bank_partner_id,
                    'status' => MandateStatus::Active,
                ]);
            });
    }

    public function withAccount(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->employee(),
        ]);
    }

    public function earning(Money $netSalary): static
    {
        return $this->state(fn (array $attributes) => ['net_salary_baisa' => $netSalary]);
    }

    public function onNotice(): static
    {
        return $this->state(fn (array $attributes) => ['employment_status' => EmploymentStatus::OnNotice]);
    }

    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => ['employment_status' => EmploymentStatus::Terminated]);
    }

    public function startedOn(string $date): static
    {
        return $this->state(fn (array $attributes) => ['employment_start_date' => $date]);
    }

    public function unverifiedSalary(): static
    {
        return $this->state(fn (array $attributes) => ['salary_verified_at' => null]);
    }
}
