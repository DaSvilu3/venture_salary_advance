<?php

namespace App\Domain\Advances;

use App\Enums\AdvanceStatus;
use App\Models\Advance;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Clock;

/**
 * The only place an advance's status changes. Every move is checked against the allowed transitions
 * and written to the audit log.
 */
class AdvanceLifecycle
{
    public function __construct(private readonly Clock $clock) {}

    /**
     * @param  array<string, mixed>  $attributes  Other columns to save with the new status.
     * @param  array<string, mixed>  $meta  Extra context for the audit log.
     *
     * @throws InvalidAdvanceTransition
     */
    public function transition(
        Advance $advance,
        AdvanceStatus $to,
        ?User $actor = null,
        array $attributes = [],
        array $meta = [],
    ): Advance {
        $from = $advance->status;

        if (! $from->canTransitionTo($to)) {
            throw InvalidAdvanceTransition::between($advance, $to);
        }

        $advance->fill($attributes);
        $advance->status = $to;

        match ($to) {
            AdvanceStatus::Disbursed => $advance->disbursed_at = $this->clock->now(),
            AdvanceStatus::Collected => $advance->forceFill([
                'collected_at' => $this->clock->now(),
                'next_collection_attempt_on' => null,
            ]),
            AdvanceStatus::Overdue, AdvanceStatus::WrittenOff => $advance->next_collection_attempt_on = null,
            default => null,
        };

        $advance->save();

        AuditLog::record("advance.{$to->value}", $advance, $actor, [
            'from' => $from->value,
            ...$meta,
        ]);

        return $advance;
    }
}
