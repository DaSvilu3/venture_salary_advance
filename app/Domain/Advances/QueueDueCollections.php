<?php

namespace App\Domain\Advances;

use App\Enums\AdvanceStatus;
use App\Enums\PayCycleStatus;
use App\Jobs\CollectAdvance;
use App\Models\Advance;
use App\Models\PayCycle;
use App\Support\Clock;

/**
 * The daily payday run: queue a direct debit for every advance due today (by the demo clock) and
 * close pay cycles whose payday has passed.
 */
class QueueDueCollections
{
    public function __construct(private readonly Clock $clock) {}

    /**
     * @return array{queued: int, closed: int, date: string}
     */
    public function handle(): array
    {
        $today = $this->clock->today();

        $dueAdvanceIds = Advance::query()
            ->whereIn('status', [AdvanceStatus::Disbursed, AdvanceStatus::CollectionFailed])
            ->whereDate('next_collection_attempt_on', '<=', $today)
            ->pluck('id');

        $dueAdvanceIds->each(fn (int $id) => CollectAdvance::dispatch($id));

        $closedCycles = PayCycle::query()
            ->where('status', PayCycleStatus::Open)
            ->whereDate('payday', '<', $today)
            ->update(['status' => PayCycleStatus::Closed]);

        return ['queued' => $dueAdvanceIds->count(), 'closed' => $closedCycles, 'date' => $today->toDateString()];
    }
}
