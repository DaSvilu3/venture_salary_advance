<?php

namespace Database\Factories;

use App\Enums\PartnerType;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => PartnerType::Bank,
            'name' => fake()->company().' Bank',
            'code' => fake()->unique()->bothify('BNK-####'),
            'brand_color' => fake()->hexColor(),
            'logo_path' => null,
        ];
    }

    public function bank(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PartnerType::Bank,
            'name' => fake()->company().' Bank',
            'code' => fake()->unique()->bothify('BNK-####'),
        ]);
    }

    public function funder(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PartnerType::Funder,
            'name' => fake()->company().' Capital',
            'code' => fake()->unique()->bothify('FND-####'),
        ]);
    }
}
