<?php

namespace Database\Factories;

use App\Enums\MandateStatus;
use App\Models\Employee;
use App\Models\Mandate;
use App\Models\Partner;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mandate>
 */
class MandateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'bank_partner_id' => Partner::factory()->bank(),
            'reference' => fake()->unique()->bothify('MND-########'),
            'max_amount_baisa' => Money::omr(1000),
            'status' => MandateStatus::Active,
            'signed_at' => now(),
            'cancelled_at' => null,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MandateStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
