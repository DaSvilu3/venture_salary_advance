<?php

namespace App\Console\Commands;

use App\Domain\Advances\QueueDueCollections;
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
    public function handle(QueueDueCollections $collections): int
    {
        $result = $collections->handle();

        $this->components->info("Queued {$result['queued']} collections and closed {$result['closed']} pay cycles for {$result['date']}.");

        return self::SUCCESS;
    }
}
