<?php

namespace Database\Factories;

use App\Enums\EmployerStatus;
use App\Models\Employer;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employer>
 */
class EmployerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' LLC',
            'cr_number' => fake()->unique()->numerify('1######'),
            'status' => EmployerStatus::Active,
            'payday_day' => 25,
            'bank_partner_id' => Partner::factory()->bank(),
            'funder_partner_id' => Partner::factory()->funder(),
            'subscription_plan' => 'standard',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => EmployerStatus::Pending]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => ['status' => EmployerStatus::Suspended]);
    }

    public function paydayOn(int $day): static
    {
        return $this->state(fn (array $attributes) => ['payday_day' => $day]);
    }
}
