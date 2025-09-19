<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;

class SendItGarbageCollector extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-it:garbage-collector';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans up old messages and logs to maintain optimal performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting garbage collection...');

        $successDelay = dbConfig('garbage_collection.success_day', 15);
        $failedDelay = dbConfig('garbage_collection.failed_day', 30);

        $successThreshold = now()->subDays($successDelay);
        $failedThreshold = now()->subDays($failedDelay);

        $deletedSuccessCount = Message::where('status', 'sent')
            ->where('sent_at', '<', $successThreshold)
            ->delete();

        $deletedFailedCount = Message::where('status', 'failed')
            ->where('sent_at', '<', $failedThreshold)
            ->delete();

        $this->info("Deleted {$deletedSuccessCount} sent messages older than {$successDelay} days.");
        $this->info("Deleted {$deletedFailedCount} failed messages older than {$failedDelay} days.");
        $this->info('Garbage collection completed.');
    }
}
