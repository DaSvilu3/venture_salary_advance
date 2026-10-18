<?php

namespace App\Console\Commands;

use App\Enums\AdvanceStatus;
use App\Enums\PayCycleStatus;
use App\Jobs\CollectAdvance;
use App\Models\Advance;
use App\Models\PayCycle;
use App\Support\Clock;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('advances:collect')]
#[Description('Queue direct-debit collection for advances due today (by the demo clock) and close finished pay cycles')]
class RunCollections extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(Clock $clock): int
    {
        $today = $clock->today();

        $dueAdvanceIds = Advance::query()
            ->whereIn('status', [AdvanceStatus::Disbursed, AdvanceStatus::CollectionFailed])
            ->whereDate('next_collection_attempt_on', '<=', $today)
            ->pluck('id');

        $dueAdvanceIds->each(fn (int $id) => CollectAdvance::dispatch($id));

        $closedCycles = PayCycle::query()
            ->where('status', PayCycleStatus::Open)
            ->whereDate('payday', '<', $today)
            ->update(['status' => PayCycleStatus::Closed]);

        $this->components->info("Queued {$dueAdvanceIds->count()} collections and closed {$closedCycles} pay cycles for {$today->toDateString()}.");

        return self::SUCCESS;
    }
}
