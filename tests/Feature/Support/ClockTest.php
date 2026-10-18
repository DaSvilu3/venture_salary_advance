<?php

namespace Tests\Feature\Support;

use App\Support\Clock;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class ClockTest extends TestCase
{
    public function test_returns_the_real_time_when_not_simulated(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-18 09:30:00'));
        $clock = app(Clock::class);

        $this->assertFalse($clock->isSimulated());
        $this->assertSame('2026-10-18 09:30:00', $clock->now()->toDateTimeString());
    }

    public function test_simulated_date_keeps_the_real_time_of_day(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-18 09:30:00'));
        $clock = app(Clock::class);

        $clock->travelTo(CarbonImmutable::parse('2026-10-25 23:59:00'));

        $this->assertTrue($clock->isSimulated());
        $this->assertSame('2026-10-25 09:30:00', $clock->now()->toDateTimeString());
        $this->assertSame('2026-10-25 00:00:00', $clock->today()->toDateTimeString());
    }

    public function test_reset_returns_to_the_real_date(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-18 09:30:00'));
        $clock = app(Clock::class);
        $clock->travelTo(CarbonImmutable::parse('2026-11-01'));

        $clock->reset();

        $this->assertFalse($clock->isSimulated());
        $this->assertSame('2026-10-18', $clock->today()->toDateString());
    }
}
