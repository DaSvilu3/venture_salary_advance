<?php

namespace Database\Factories;

use App\Models\Disclosure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Disclosure>
 */
class DisclosureFactory extends Factory
{
    /**
     * A published disclosure.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version' => fake()->unique()->numerify('2026.##'),
            'body_en' => 'You are taking a salary advance. The amount and the fee shown are collected from your bank account by direct debit on your payday.',
            'body_ar' => 'أنت تحصل على سلفة من راتبك. يُحصَّل المبلغ والرسوم الموضّحة من حسابك المصرفي عبر الخصم المباشر في يوم الراتب.',
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['published_at' => null]);
    }
}
