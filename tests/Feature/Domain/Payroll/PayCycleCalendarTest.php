<?php

namespace Tests\Feature\Domain\Payroll;

use App\Domain\Payroll\PayCycleCalendar;
use App\Models\Employer;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PayCycleCalendarTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{int, string, string}>
     */
    public static function paydays(): array
    {
        return [
            'before payday in the month' => [25, '2026-10-10', '2026-10-25'],
            'on payday itself' => [25, '2026-10-25', '2026-10-25'],
            'after payday rolls to next month' => [25, '2026-10-26', '2026-11-25'],
            'day 31 falls on the last day of November' => [31, '2026-11-02', '2026-11-30'],
            'day 30 falls on 28 February' => [30, '2027-02-01', '2027-02-28'],
            'rolling over the year end' => [25, '2026-12-28', '2027-01-25'],
        ];
    }

    #[DataProvider('paydays')]
    public function test_finds_the_next_payday_on_or_after_a_date(int $paydayDay, string $date, string $expectedPayday): void
    {
        $employer = Employer::factory()->paydayOn($paydayDay)->make();

        $payday = app(PayCycleCalendar::class)->paydayOnOrAfter($employer, CarbonImmutable::parse($date));

        $this->assertSame($expectedPayday, $payday->toDateString());
    }

    public function test_last_request_date_is_the_cutoff_before_payday(): void
    {
        $lastRequestDate = app(PayCycleCalendar::class)->lastRequestDate(CarbonImmutable::parse('2026-10-25'), 3);

        $this->assertSame('2026-10-22', $lastRequestDate->toDateString());
    }

    public function test_creates_each_cycle_once_starting_the_day_after_the_previous_payday(): void
    {
        $employer = Employer::factory()->paydayOn(25)->create();
        $calendar = app(PayCycleCalendar::class);

        $cycle = $calendar->cycleEndingOn($employer, CarbonImmutable::parse('2026-10-25'));
        $again = $calendar->cycleEndingOn($employer, CarbonImmutable::parse('2026-10-25'));

        $this->assertTrue($cycle->is($again));
        $this->assertSame('2026-09-26', $cycle->period_start->toDateString());
        $this->assertSame(1, $employer->payCycles()->count());
    }
}
