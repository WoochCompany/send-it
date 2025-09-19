<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\MessageProvider;
use App\Services\Message\MessageScheduler;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScheduleMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Message $message,
        public ?Carbon $requestedTime = null
    ) {
    }

    public static function dispatchFromMessage(Message $message): void
    {
        Log::info('Dispatching message scheduling job', ['scheduled_at' => $message->scheduled_at,'scheduled_requested_at' => $message->scheduled_requested_at, 'uuid' => $message->id]);
        self::dispatch($message, $message->scheduled_at ?? now());
    }

    /**
     * Execute the job.
     */
    public function handle(MessageScheduler $scheduler): void
    {
        // Use message's provider or default
        $provider = $this->message->provider ?? MessageProvider::default();

        if (!$provider) {
            throw new \RuntimeException('No message provider available');
        }

        // Schedule the message respecting rate limits
        $scheduledTime = $scheduler->scheduleMessage(
            $this->message,
            $provider,
            $this->requestedTime
        );

        // Dispatch SendMessageJob for the scheduled time
        SendMessageJob::dispatch($this->message)->delay($scheduledTime);

        // Update message status
        $this->message->update(['status' => 'scheduled']);
    }
}
