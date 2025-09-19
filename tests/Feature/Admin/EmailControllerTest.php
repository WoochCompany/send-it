<?php

namespace Tests\Feature\Admin;

use App\Models\Message;
use App\Models\MessageEvent;
use App\Models\MessageProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_can_view_emails_index(): void
    {
        $provider = MessageProvider::factory()->create([
            'name' => 'Test Provider',
            'slug' => 'test-provider',
        ]);

        $messages = Message::factory()->count(3)->create([
            'message_provider_id' => $provider->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/admin/emails');

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Admin/Emails/Index')
                ->has('messages.data', 3)
                ->has('statusOptions')
                ->has('filters')
        );
    }

    public function test_can_filter_emails_by_status(): void
    {
        Message::factory()->create(['status' => 'sent']);
        Message::factory()->create(['status' => 'pending']);
        Message::factory()->create(['status' => 'failed']);

        $response = $this->actingAs($this->user)
            ->get('/admin/emails?status=sent');

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->has('messages.data', 1)
                ->where('filters.status', 'sent')
        );
    }

    public function test_can_search_emails(): void
    {
        Message::factory()->create([
            'recipient' => 'john@example.com',
            'subject' => 'Welcome message',
        ]);
        Message::factory()->create([
            'recipient' => 'jane@example.com',
            'subject' => 'Newsletter',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/admin/emails?search=john');

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->has('messages.data', 1)
                ->where('filters.search', 'john')
        );
    }

    public function test_can_view_email_details(): void
    {
        $provider = MessageProvider::factory()->create();
        $message = Message::factory()->create([
            'message_provider_id' => $provider->id,
        ]);

        MessageEvent::factory()->create([
            'message_id' => $message->id,
            'type' => 'message_sent',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/admin/emails/{$message->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Admin/Emails/Show')
                ->where('message.id', $message->id)
                ->has('message.provider')
                ->has('message.events', 1)
        );
    }

    public function test_emails_are_ordered_by_created_at_desc(): void
    {
        $oldMessage = Message::factory()->create([
            'created_at' => now()->subDays(2),
        ]);
        $newMessage = Message::factory()->create([
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->user)
            ->get('/admin/emails');

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->where('messages.data.0.id', $newMessage->id)
                ->where('messages.data.1.id', $oldMessage->id)
        );
    }

    public function test_requires_authentication(): void
    {
        $response = $this->get('/admin/emails');
        $response->assertRedirect('/login');

        $message = Message::factory()->create();
        $response = $this->get("/admin/emails/{$message->id}");
        $response->assertRedirect('/login');
    }
}
