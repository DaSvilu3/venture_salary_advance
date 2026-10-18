<?php

namespace Database\Factories;

use App\Domain\Payroll\PayCycleCalendar;
use App\Enums\AdvanceStatus;
use App\Models\Advance;
use App\Models\Disclosure;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Mandate;
use App\Models\PayCycle;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Advance>
 */
class AdvanceFactory extends Factory
{
    /**
     * An advance that has been paid out and is waiting for collection on payday. Pass the employee with
     * for() — use an enrolled() employee so the mandate, partners and cycle line up.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => fake()->unique()->bothify('SLA-########'),
            'employee_id' => Employee::factory()->enrolled(),
            'employer_id' => fn (array $attributes) => Employee::query()->whereKey($attributes['employee_id'])->firstOrFail()->employer_id,
            'pay_cycle_id' => fn (array $attributes) => app(PayCycleCalendar::class)->cycleEndingOn(
                Employer::query()->whereKey($attributes['employer_id'])->firstOrFail(),
                CarbonImmutable::now()->addDays(10)->startOfDay(),
            )->id,
            'mandate_id' => fn (array $attributes) => Mandate::query()->where('employee_id', $attributes['employee_id'])->value('id')
                ?? Mandate::factory()->create(['employee_id' => $attributes['employee_id']])->id,
            'bank_partner_id' => fn (array $attributes) => Employee::query()->whereKey($attributes['employee_id'])->firstOrFail()->employer->bank_partner_id,
            'funder_partner_id' => fn (array $attributes) => Employee::query()->whereKey($attributes['employee_id'])->firstOrFail()->employer->funder_partner_id,
            'disclosure_id' => Disclosure::factory(),
            'amount_baisa' => Money::omr(100),
            'fee_baisa' => Money::omr('2.500'),
            'total_due_baisa' => Money::omr('102.500'),
            'due_date' => fn (array $attributes) => PayCycle::query()->whereKey($attributes['pay_cycle_id'])->firstOrFail()->payday,
            'status' => AdvanceStatus::Disbursed,
            'disclosure_accepted_at' => now(),
            'requested_at' => now(),
            'disbursed_at' => now(),
            'collected_at' => null,
            'collection_attempts' => 0,
            'next_collection_attempt_on' => fn (array $attributes) => $attributes['due_date'],
            'failure_reason' => null,
        ];
    }

    public function inStatus(AdvanceStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    public function forAmount(Money $amount, ?Money $fee = null): static
    {
        $fee ??= Money::omr('2.500');

        return $this->state(fn (array $attributes) => [
            'amount_baisa' => $amount,
            'fee_baisa' => $fee,
            'total_due_baisa' => $amount->plus($fee),
        ]);
    }
}
