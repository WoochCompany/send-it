<?php

namespace Tests\Feature;

use App\Jobs\ScheduleMessageJob;
use App\Jobs\SendMessageJob;
use App\Models\MessageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class MessageProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_message_with_provider_slug_and_job_dispatched(): void
    {
        Bus::fake();

        $provider = MessageProvider::create([
            'slug' => 'smtp-test',
            'name' => 'SMTP Test',
            'provider' => 'smtp',
            'config' => null,
        ]);

        $payload = [
            'recipient' => 'user@example.com',
            'subject' => 'Hello',
            'body' => 'Body here',
            'provider_slug' => $provider->slug,
        ];

        $response = $this->postJson('/api/messages', $payload);

        $response->assertStatus(201)->assertJsonStructure(['id', 'status']);

        $this->assertDatabaseHas('messages', [
            'recipient' => 'user@example.com',
            'subject' => 'Hello',
            'message_provider_id' => $provider->id,
        ]);

        Bus::assertDispatched(ScheduleMessageJob::class);
    }
}

