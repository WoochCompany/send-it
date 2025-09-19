<?php

namespace Tests\Unit;

use App\Jobs\ScheduleMessageJob;
use App\Jobs\SendMessageJob;
use App\Models\Message;
use App\Models\MessageProvider;
use App\Services\Message\MessageScheduler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ScheduleMessageJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_message_job_with_default_provider(): void
    {
        Queue::fake();

        $defaultProvider = MessageProvider::create([
            'slug' => 'default-provider',
            'name' => 'Default Provider',
            'provider' => 'smtp',
            'config' => [],
            'is_default' => true,
            'messages_per_minute' => 10,
        ]);

        $message = Message::factory()->create();
        $requestedTime = now()->addMinutes(5);

        $job = new ScheduleMessageJob($message, $requestedTime);
        $job->handle(new MessageScheduler());

        // Verify message was scheduled
        $this->assertEquals('scheduled', $message->fresh()->status);

        // Verify SendMessageJob was dispatched
        Queue::assertPushed(SendMessageJob::class, function ($job) use ($message) {
            return $job->message->id === $message->id;
        });
    }

    public function test_schedule_message_job_with_specific_provider(): void
    {
        Queue::fake();

        $provider = MessageProvider::create([
            'slug' => 'specific-provider',
            'name' => 'Specific Provider',
            'provider' => 'log',
            'config' => [],
            'messages_per_minute' => 5,
        ]);

        $message = Message::factory()->create(['message_provider_id' => $provider->id]);
        $requestedTime = now()->addMinutes(10);

        $job = new ScheduleMessageJob($message, $requestedTime);
        $job->handle(new MessageScheduler());

        // Verify message was scheduled with correct provider
        $this->assertEquals('scheduled', $message->fresh()->status);
        $this->assertEquals($provider->id, $message->fresh()->message_provider_id);

        Queue::assertPushed(SendMessageJob::class);
    }

    public function test_schedule_message_job_throws_exception_without_provider(): void
    {
        $message = Message::factory()->create();

        $job = new ScheduleMessageJob($message, now());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No message provider available');

        $job->handle(new MessageScheduler());
    }

    public function test_schedule_message_job_respects_rate_limits(): void
    {
        Queue::fake();

        $provider = MessageProvider::create([
            'slug' => 'limited-provider',
            'name' => 'Limited Provider',
            'provider' => 'smtp',
            'config' => [],
            'messages_per_minute' => 1,
        ]);

        $requestedTime = now()->addMinutes(5);

        // Fill the rate limit for the requested minute
        Message::factory()->create([
            'message_provider_id' => $provider->id,
            'scheduled_at' => $requestedTime->copy()->startOfMinute(),
            'status' => 'scheduled',
        ]);

        $message = Message::factory()->create(['message_provider_id' => $provider->id]);
        $job = new ScheduleMessageJob($message, $requestedTime);
        $job->handle(new MessageScheduler());

        // Message should be rescheduled to next minute
        $expectedTime = $requestedTime->copy()->addMinute()->startOfMinute();
        $this->assertEquals($expectedTime->toDateTimeString(), $message->fresh()->scheduled_at);

        // Verify reschedule event was logged
        $this->assertDatabaseHas('message_events', [
            'message_id' => $message->id,
            'type' => 'message_rescheduled',
        ]);

        Queue::assertPushed(SendMessageJob::class);
    }
}
