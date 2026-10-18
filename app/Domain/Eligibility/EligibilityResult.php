<?php

namespace App\Domain\Eligibility;

use App\Enums\IneligibilityReason;
use App\Models\ProgramTerms;
use App\Support\Money;
use Carbon\CarbonImmutable;

/**
 * Whether an employee can take an advance now, how much, and why not.
 */
final readonly class EligibilityResult
{
    /**
     * @param  list<IneligibilityReason>  $reasons
     */
    public function __construct(
        public array $reasons,
        public Money $limit,
        public Money $drawn,
        public Money $available,
        public CarbonImmutable $payday,
        public CarbonImmutable $lastRequestDate,
        public int $drawsThisCycle,
        public ProgramTerms $terms,
    ) {}

    public function isEligible(): bool
    {
        return $this->reasons === [];
    }

    public function hasReason(IneligibilityReason $reason): bool
    {
        return in_array($reason, $this->reasons, true);
    }

    /**
     * @return array{eligible: bool, reasons: list<array{code: string, message: string}>, limit: int, drawn: int, available: int, minimum: int, fee: int, payday: string, last_request_date: string, draws_this_cycle: int, max_draws: int}
     */
    public function toArray(): array
    {
        return [
            'eligible' => $this->isEligible(),
            'reasons' => array_map(
                fn (IneligibilityReason $reason): array => ['code' => $reason->value, 'message' => $reason->message()],
                $this->reasons,
            ),
            'limit' => $this->limit->baisa,
            'drawn' => $this->drawn->baisa,
            'available' => $this->available->baisa,
            'minimum' => $this->terms->min_advance_baisa->baisa,
            'fee' => $this->terms->flat_fee_baisa->baisa,
            'payday' => $this->payday->toDateString(),
            'last_request_date' => $this->lastRequestDate->toDateString(),
            'draws_this_cycle' => $this->drawsThisCycle,
            'max_draws' => $this->terms->max_draws_per_cycle,
        ];
    }
}
