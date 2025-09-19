<?php

namespace Tests\Feature;

use App\Jobs\ScheduleMessageJob;
use App\Jobs\SendMessageJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApiSendMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_message_and_dispatches_job(): void
    {
        Bus::fake();

        $payload = [
            'recipient' => 'test@example.com',
            'subject' => 'Hello',
            'body' => 'Test body',
            'tags' => ['welcome', 'txn'],
        ];

        $response = $this->postJson('/api/messages', $payload);

        $response->assertStatus(201)->assertJsonStructure(['id', 'status']);

        $this->assertDatabaseHas('messages', [
            'recipient' => 'test@example.com',
            'subject' => 'Hello',
            'status' => 'pending',
        ]);

        // Job dispatched
        Bus::assertDispatched(ScheduleMessageJob::class);

        // Tags created with Ucfirst
        $this->assertDatabaseHas('tags', ['name' => 'Welcome']);
        $this->assertDatabaseHas('tags', ['name' => 'Txn']);

        // Event recorded
        $this->assertDatabaseHas('message_events', ['type' => 'message_created']);
    }

    public function test_message_has_default_retry_counter(): void
    {
        $message = \App\Models\Message::factory()->create();
        $this->assertEquals(0, $message->retry_counter);
    }
}
