<?php

namespace Tests\Unit;

use App\Models\Message;
use App\Models\MessageEvent;
use App\Models\MessageProvider;
use App\Services\Message\MessageScheduler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageSchedulerTest extends TestCase
{
    use RefreshDatabase;

    private MessageScheduler $scheduler;
    private MessageProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scheduler = new MessageScheduler();

        $this->provider = MessageProvider::create([
            'slug' => 'test-provider',
            'name' => 'Test Provider',
            'provider' => 'smtp',
            'config' => ['host' => 'smtp.test.com'],
            'messages_per_minute' => 2, // Low limit for testing
        ]);
    }

    public function test_schedule_message_without_rate_limit(): void
    {
        $provider = MessageProvider::create([
            'slug' => 'unlimited-provider',
            'name' => 'Unlimited Provider',
            'provider' => 'smtp',
            'config' => [],
            'messages_per_minute' => 0, // No limit
        ]);

        $message = Message::factory()->create();
        $requestedTime = now()->addMinutes(5);

        $scheduledTime = $this->scheduler->scheduleMessage($message, $provider, $requestedTime);

        $this->assertEquals($requestedTime->toDateTimeString(), $scheduledTime->toDateTimeString());
        $this->assertEquals($requestedTime->toDateTimeString(), $message->fresh()->scheduled_at);
        $this->assertEquals($provider->id, $message->fresh()->message_provider_id);
    }

    public function test_schedule_message_within_rate_limit(): void
    {
        $message = Message::factory()->create();
        $requestedTime = now()->addMinutes(5);

        $scheduledTime = $this->scheduler->scheduleMessage($message, $this->provider, $requestedTime);

        // Should be scheduled at requested time since we're within the limit
        $this->assertEquals($requestedTime->toDateTimeString(), $scheduledTime->toDateTimeString());
        $this->assertEquals($requestedTime->toDateTimeString(), $message->fresh()->scheduled_at);
    }

    public function test_schedule_message_exceeds_rate_limit(): void
    {
        $requestedTime = now()->addMinutes(5);

        // Create messages that fill the rate limit for the requested minute
        Message::factory()->count(2)->create([
            'message_provider_id' => $this->provider->id,
            'scheduled_at' => $requestedTime->copy()->startOfMinute(),
            'status' => 'scheduled',
        ]);

        $message = Message::factory()->create();
        $scheduledTime = $this->scheduler->scheduleMessage($message, $this->provider, $requestedTime);

        // Should be rescheduled to the next minute
        $expectedTime = $requestedTime->copy()->addMinute()->startOfMinute();
        $this->assertEquals($expectedTime->toDateTimeString(), $scheduledTime->toDateTimeString());

        // Check that a reschedule event was logged
        $this->assertDatabaseHas('message_events', [
            'message_id' => $message->id,
            'type' => 'message_rescheduled',
        ]);

        $event = MessageEvent::where('message_id', $message->id)
            ->where('type', 'message_rescheduled')
            ->first();

        $payload = json_decode($event->payload, true);
        $this->assertEquals($requestedTime->toISOString(), $payload['old_scheduled_time']);
        $this->assertEquals($scheduledTime->toISOString(), $payload['new_scheduled_time']);
        $this->assertEquals('rate_limit_exceeded', $payload['reason']);
    }

    public function test_batch_schedule_messages(): void
    {
        $messages = Message::factory()->count(5)->create();
        $startTime = now()->addMinutes(10);

        $scheduledTimes = $this->scheduler->batchScheduleMessages($messages, $this->provider, $startTime);

        $this->assertCount(5, $scheduledTimes);

        // First two messages should be in the first minute
        $this->assertEquals($startTime->toDateTimeString(), $scheduledTimes[0]->toDateTimeString());
        $this->assertEquals($startTime->toDateTimeString(), $scheduledTimes[1]->toDateTimeString());

        // Next two messages should be in the second minute
        $expectedSecondMinute = $startTime->copy()->addMinute()->startOfMinute();
        $this->assertEquals($expectedSecondMinute->toDateTimeString(), $scheduledTimes[2]->toDateTimeString());
        $this->assertEquals($expectedSecondMinute->toDateTimeString(), $scheduledTimes[3]->toDateTimeString());

        // Fifth message should be in the third minute
        $expectedThirdMinute = $startTime->copy()->addMinutes(2)->startOfMinute();
        $this->assertEquals($expectedThirdMinute->toDateTimeString(), $scheduledTimes[4]->toDateTimeString());
    }

    public function test_get_estimated_schedule_time(): void
    {
        $requestedTime = now()->addMinutes(5);

        // Fill the rate limit for the requested minute
        Message::factory()->count(2)->create([
            'message_provider_id' => $this->provider->id,
            'scheduled_at' => $requestedTime->copy()->startOfMinute(),
            'status' => 'scheduled',
        ]);

        $estimatedTime = $this->scheduler->getEstimatedScheduleTime($this->provider, $requestedTime);

        $expectedTime = $requestedTime->copy()->addMinute()->startOfMinute();
        $this->assertEquals($expectedTime->toDateTimeString(), $estimatedTime->toDateTimeString());
    }

    public function test_get_provider_utilization(): void
    {
        $fromTime = now()->addMinutes(10)->startOfMinute();

        // Schedule some messages in the first minute
        Message::factory()->count(1)->create([
            'message_provider_id' => $this->provider->id,
            'scheduled_at' => $fromTime->copy(),
            'status' => 'scheduled',
        ]);

        // Schedule messages in the second minute (full capacity)
        Message::factory()->count(2)->create([
            'message_provider_id' => $this->provider->id,
            'scheduled_at' => $fromTime->copy()->addMinute(),
            'status' => 'scheduled',
        ]);

        $utilization = $this->scheduler->getProviderUtilization($this->provider, $fromTime, 3);

        $this->assertCount(3, $utilization);

        // First minute: 1/2 messages (50% utilization)
        $this->assertEquals(1, $utilization[0]['scheduled_messages']);
        $this->assertEquals(2, $utilization[0]['capacity']);
        $this->assertEquals(50.0, $utilization[0]['utilization_percentage']);

        // Second minute: 2/2 messages (100% utilization)
        $this->assertEquals(2, $utilization[1]['scheduled_messages']);
        $this->assertEquals(2, $utilization[1]['capacity']);
        $this->assertEquals(100.0, $utilization[1]['utilization_percentage']);

        // Third minute: 0/2 messages (0% utilization)
        $this->assertEquals(0, $utilization[2]['scheduled_messages']);
        $this->assertEquals(2, $utilization[2]['capacity']);
        $this->assertEquals(0.0, $utilization[2]['utilization_percentage']);
    }

    public function test_schedule_message_ignores_sent_and_failed_messages(): void
    {
        $requestedTime = now()->addMinutes(5);

        // Create sent and failed messages that shouldn't count towards the limit
        Message::factory()->create([
            'message_provider_id' => $this->provider->id,
            'scheduled_at' => $requestedTime->copy()->startOfMinute(),
            'status' => 'sent',
        ]);

        Message::factory()->create([
            'message_provider_id' => $this->provider->id,
            'scheduled_at' => $requestedTime->copy()->startOfMinute(),
            'status' => 'failed',
        ]);

        $message = Message::factory()->create();
        $scheduledTime = $this->scheduler->scheduleMessage($message, $this->provider, $requestedTime);

        // Should be scheduled at requested time since sent/failed messages don't count
        $this->assertEquals($requestedTime->toDateTimeString(), $scheduledTime->toDateTimeString());
    }

    public function test_message_provider_helper_methods(): void
    {
        $from = now();
        $to = now()->addMinutes(5);

        Message::factory()->count(3)->create([
            'message_provider_id' => $this->provider->id,
            'scheduled_at' => $from->copy()->addMinutes(2),
            'status' => 'scheduled',
        ]);

        $count = $this->provider->getScheduledMessagesCount($from, $to);
        $this->assertEquals(3, $count);

        $this->assertTrue($this->provider->hasRateLimit());

        $unlimitedProvider = MessageProvider::create([
            'slug' => 'unlimited',
            'name' => 'Unlimited',
            'provider' => 'log',
            'config' => [],
            'messages_per_minute' => 0,
        ]);

        $this->assertFalse($unlimitedProvider->hasRateLimit());
    }
}
