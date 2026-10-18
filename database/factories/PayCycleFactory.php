<?php

namespace Database\Factories;

use App\Enums\PayCycleStatus;
use App\Models\Employer;
use App\Models\PayCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayCycle>
 */
class PayCycleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $payday = now()->addDays(10)->startOfDay();

        return [
            'employer_id' => Employer::factory(),
            'period_start' => $payday->subMonthNoOverflow()->addDay()->toDateString(),
            'payday' => $payday->toDateString(),
            'status' => PayCycleStatus::Open,
        ];
    }
}
