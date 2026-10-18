<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;

/**
 * The application's notion of "now" for domain logic.
 *
 * In demos an admin can move the simulated date forward (for example to payday) so the full
 * advance lifecycle can be shown in minutes. The real time of day is kept.
 */
class Clock
{
    private const CACHE_KEY = 'clock.simulated_date';

    public function __construct(private readonly Repository $cache) {}

    public function now(): CarbonImmutable
    {
        $now = CarbonImmutable::now();
        $simulatedDate = $this->simulatedDate();

        if ($simulatedDate === null) {
            return $now;
        }

        return $simulatedDate->setTime($now->hour, $now->minute, $now->second);
    }

    public function today(): CarbonImmutable
    {
        return $this->now()->startOfDay();
    }

    public function simulatedDate(): ?CarbonImmutable
    {
        $date = $this->cache->get(self::CACHE_KEY);

        return is_string($date) ? CarbonImmutable::parse($date)->startOfDay() : null;
    }

    public function isSimulated(): bool
    {
        return $this->simulatedDate() !== null;
    }

    public function travelTo(DateTimeInterface $date): void
    {
        $this->cache->forever(self::CACHE_KEY, CarbonImmutable::instance($date)->toDateString());
    }

    public function reset(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }
}
