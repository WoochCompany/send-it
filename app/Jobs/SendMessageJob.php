<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\MessageEvent;
use App\Services\Message\ProviderFactory;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private static int $MAX_RETRY = 3;

    function getMaxRetry(): int
    {
        return dbConfig('message.max_retry', self::$MAX_RETRY);
    }

    public function __construct(public Message $message)
    {
    }

    public function handle(ProviderFactory $factory): void
    {
        try {
            // Resolve connector based on message provider (or default)
            $provider = $this->message->provider;
            $connector = $factory->create($provider);

            $connector->send($this->message);

            $this->message->update([
                'status' => 'sent',
                'sent_at' => Carbon::now(),
            ]);

            MessageEvent::create([
                'message_id' => $this->message->id,
                'type' => 'message_sent',
                'payload' => ['sent_at' => Carbon::now()->toDateTimeString()],
            ]);
        } catch (\Throwable $e) {
            if($this->message->retry_counter < $this->getMaxRetry()) {
                // Increment retry counter
                $this->message->increment('retry_counter');

                //retry delayed by 2^n minutes
                $delayMinutes = pow(2, $this->message->retry_counter);

                MessageEvent::create([
                    'message_id' => $this->message->id,
                    'type' => 'send_retry_scheduled',
                    'payload' => [
                        'error' => $e->getMessage(),
                        'retry_in_minutes' => $delayMinutes,
                        'retry_count' => $this->message->retry_counter,
                        'reason' => 'Retrying send due to failure',
                    ],
                ]);
                ScheduleMessageJob::dispatch($this->message, now()->addMinutes($delayMinutes));

                return;
            }
            $this->message->update(['status' => 'failed']);

            MessageEvent::create([
                'message_id' => $this->message->id,
                'type' => 'send_failed',
                'payload' => ['error' => $e->getMessage()],
            ]);
        }
    }
}
