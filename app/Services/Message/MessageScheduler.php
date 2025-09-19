<?php

namespace App\Services\Message;

use App\Models\Message;
use App\Models\MessageEvent;
use App\Models\MessageProvider;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MessageScheduler
{
    /**
     * Schedule a message, respecting the provider's messages_per_minute limit.
     *
     * @param Message $message
     * @param MessageProvider $provider
     * @param Carbon|null $requestedTime
     * @return Carbon The actual scheduled time
     */
    public function scheduleMessage(Message $message, MessageProvider $provider, ?Carbon $requestedTime = null): Carbon
    {
        $requestedTime = $requestedTime ?? now();
        $scheduledTime = $this->findNextAvailableSlot($provider, $requestedTime);

        // If the scheduled time is different from requested time, log the reschedule event
        if (!$scheduledTime->equalTo($requestedTime)) {
            $this->logRescheduleEvent($message, $requestedTime, $scheduledTime);
        }

        $message->update([
            'scheduled_at' => $scheduledTime,
            'message_provider_id' => $provider->id,
        ]);

        return $scheduledTime;
    }

    /**
     * Find the next available time slot for a message given the provider's rate limit.
     *
     * @param MessageProvider $provider
     * @param Carbon $requestedTime
     * @return Carbon
     */
    protected function findNextAvailableSlot(MessageProvider $provider, Carbon $requestedTime): Carbon
    {
        $messagesPerMinute = $provider->messages_per_minute;

        if ($messagesPerMinute <= 0) {
            // No rate limit
            return $requestedTime;
        }

        $currentSlot = $requestedTime->copy()->startOfMinute();

        while (true) {
            $messagesInSlot = $this->getScheduledMessagesCount($provider, $currentSlot);

            if ($messagesInSlot < $messagesPerMinute) {
                // Found available slot, return the requested time within this minute
                if ($currentSlot->equalTo($requestedTime->copy()->startOfMinute())) {
                    return $requestedTime;
                }

                // Return the start of the available minute slot
                return $currentSlot->copy();
            }

            // Move to next minute
            $currentSlot->addMinute();
        }
    }

    /**
     * Get the count of messages scheduled for a specific minute slot.
     *
     * @param MessageProvider $provider
     * @param Carbon $minuteSlot
     * @return int
     */
    protected function getScheduledMessagesCount(MessageProvider $provider, Carbon $minuteSlot): int
    {
        return Message::where('message_provider_id', $provider->id)
            ->whereBetween('scheduled_at', [
                $minuteSlot->copy(),
                $minuteSlot->copy()->addMinute()->subSecond()
            ])
            ->whereNotIn('status', ['sent', 'failed'])
            ->count();
    }

    /**
     * Log a message reschedule event.
     *
     * @param Message $message
     * @param Carbon $oldScheduledTime
     * @param Carbon $newScheduledTime
     * @return void
     */
    protected function logRescheduleEvent(Message $message, Carbon $oldScheduledTime, Carbon $newScheduledTime): void
    {
        MessageEvent::create([
            'message_id' => $message->id,
            'type' => 'message_rescheduled',
            'payload' => json_encode([
                'old_scheduled_time' => $oldScheduledTime->toISOString(),
                'new_scheduled_time' => $newScheduledTime->toISOString(),
                'reason' => 'rate_limit_exceeded',
            ]),
        ]);
    }

    /**
     * Batch schedule multiple messages optimally.
     *
     * @param Collection $messages
     * @param MessageProvider $provider
     * @param Carbon|null $startTime
     * @return Collection Collection of scheduled times
     */
    public function batchScheduleMessages(Collection $messages, MessageProvider $provider, ?Carbon $startTime = null): Collection
    {
        $startTime = $startTime ?? now();
        $scheduledTimes = collect();
        $currentTime = $startTime->copy();

        foreach ($messages as $message) {
            $scheduledTime = $this->scheduleMessage($message, $provider, $currentTime);
            $scheduledTimes->push($scheduledTime);

            // For next message, start from the same minute to fill slots efficiently
            if ($scheduledTime->greaterThan($currentTime)) {
                $currentTime = $scheduledTime->copy();
            }
        }

        return $scheduledTimes;
    }

    /**
     * Get the estimated time for scheduling a new message.
     *
     * @param MessageProvider $provider
     * @param Carbon|null $requestedTime
     * @return Carbon
     */
    public function getEstimatedScheduleTime(MessageProvider $provider, ?Carbon $requestedTime = null): Carbon
    {
        $requestedTime = $requestedTime ?? now();
        return $this->findNextAvailableSlot($provider, $requestedTime);
    }

    /**
     * Get provider utilization statistics.
     *
     * @param MessageProvider $provider
     * @param Carbon|null $fromTime
     * @param int $minutesToAnalyze
     * @return array
     */
    public function getProviderUtilization(MessageProvider $provider, ?Carbon $fromTime = null, int $minutesToAnalyze = 60): array
    {
        $fromTime = $fromTime ?? now();
        $toTime = $fromTime->copy()->addMinutes($minutesToAnalyze);

        $scheduledMessages = Message::where('message_provider_id', $provider->id)
            ->whereBetween('scheduled_at', [$fromTime, $toTime])
            ->whereNotIn('status', ['sent', 'failed'])
            ->orderBy('scheduled_at')
            ->get();

        $utilization = [];
        $currentMinute = $fromTime->copy()->startOfMinute();

        while ($currentMinute->lessThan($toTime)) {
            $messagesInMinute = $scheduledMessages->filter(function ($message) use ($currentMinute) {
                $scheduledMinute = Carbon::parse($message->scheduled_at)->startOfMinute();
                return $scheduledMinute->equalTo($currentMinute);
            })->count();

            $utilization[] = [
                'minute' => $currentMinute->toISOString(),
                'scheduled_messages' => $messagesInMinute,
                'capacity' => $provider->messages_per_minute,
                'utilization_percentage' => $provider->messages_per_minute > 0
                    ? round(($messagesInMinute / $provider->messages_per_minute) * 100, 2)
                    : 0,
            ];

            $currentMinute->addMinute();
        }

        return $utilization;
    }
}
