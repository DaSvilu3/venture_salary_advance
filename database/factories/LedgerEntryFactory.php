<?php

namespace Database\Factories;

use App\Enums\LedgerEntryStatus;
use App\Enums\LedgerEntryType;
use App\Models\Advance;
use App\Models\LedgerEntry;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'advance_id' => Advance::factory(),
            'type' => LedgerEntryType::Disbursement,
            'status' => LedgerEntryStatus::Succeeded,
            'amount_baisa' => Money::omr(100),
            'provider' => 'fake-bank',
            'external_reference' => fake()->uuid(),
            'failure_reason' => null,
            'occurred_at' => now(),
        ];
    }
}
