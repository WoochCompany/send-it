<?php

namespace Tests\Feature\Admin;

use App\Models\Message;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTagFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and authenticate
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_can_filter_messages_by_single_tag(): void
    {
        // Create tags
        $urgentTag = Tag::factory()->create(['name' => 'Urgent']);
        $marketingTag = Tag::factory()->create(['name' => 'Marketing']);

        // Create messages with tags
        $urgentMessage = Message::factory()->create(['subject' => 'Urgent notification']);
        $urgentMessage->tags()->attach($urgentTag);

        $marketingMessage = Message::factory()->create(['subject' => 'Marketing campaign']);
        $marketingMessage->tags()->attach($marketingTag);

        $untaggedMessage = Message::factory()->create(['subject' => 'No tags']);

        // Filter by urgent tag
        $response = $this->get(route('admin.emails.index', ['tags' => [$urgentTag->id]]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Emails/Index')
            ->has('messages.data', 1)
            ->where('messages.data.0.subject', 'Urgent notification')
        );
    }

    public function test_can_filter_messages_by_multiple_tags(): void
    {
        // Create tags
        $urgentTag = Tag::factory()->create(['name' => 'Urgent']);
        $importantTag = Tag::factory()->create(['name' => 'Important']);
        $marketingTag = Tag::factory()->create(['name' => 'Marketing']);

        // Create messages
        $urgentMessage = Message::factory()->create(['subject' => 'Urgent only']);
        $urgentMessage->tags()->attach($urgentTag);

        $urgentImportantMessage = Message::factory()->create(['subject' => 'Urgent and Important']);
        $urgentImportantMessage->tags()->attach([$urgentTag, $importantTag]);

        $marketingMessage = Message::factory()->create(['subject' => 'Marketing only']);
        $marketingMessage->tags()->attach($marketingTag);

        // Filter by urgent and important tags
        $response = $this->get(route('admin.emails.index', [
            'tags' => [$urgentTag->id, $importantTag->id]
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Emails/Index')
            ->has('messages.data', 2) // Should return messages with either urgent OR important tags
            ->where('filters.tags', [$urgentTag->id, $importantTag->id])
        );
    }

    public function test_returns_all_available_tags(): void
    {
        // Create some tags
        $tag1 = Tag::factory()->create(['name' => 'Important']);
        $tag2 = Tag::factory()->create(['name' => 'Marketing']);
        $tag3 = Tag::factory()->create(['name' => 'Urgent']);

        $response = $this->get(route('admin.emails.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Emails/Index')
            ->has('availableTags', 3)
            ->where('availableTags.0.name', 'Important') // Ordered by name
            ->where('availableTags.1.name', 'Marketing')
            ->where('availableTags.2.name', 'Urgent')
        );
    }

    public function test_can_combine_tag_filter_with_other_filters(): void
    {
        // Create tag and messages
        $urgentTag = Tag::factory()->create(['name' => 'Urgent']);

        $sentUrgentMessage = Message::factory()->create([
            'subject' => 'Sent urgent message',
            'status' => 'sent'
        ]);
        $sentUrgentMessage->tags()->attach($urgentTag);

        $pendingUrgentMessage = Message::factory()->create([
            'subject' => 'Pending urgent message',
            'status' => 'pending'
        ]);
        $pendingUrgentMessage->tags()->attach($urgentTag);

        // Filter by urgent tag AND sent status
        $response = $this->get(route('admin.emails.index', [
            'tags' => [$urgentTag->id],
            'status' => 'sent'
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Emails/Index')
            ->has('messages.data', 1)
            ->where('messages.data.0.subject', 'Sent urgent message')
            ->where('filters.tags', [$urgentTag->id])
            ->where('filters.status', 'sent')
        );
    }

    public function test_tag_filter_preserves_query_string_in_pagination(): void
    {
        $tag = Tag::factory()->create(['name' => 'Test']);

        // Create enough messages to trigger pagination
        for ($i = 0; $i < 20; $i++) {
            $message = Message::factory()->create();
            $message->tags()->attach($tag);
        }

        $response = $this->get(route('admin.emails.index', ['tags' => [$tag->id]]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Emails/Index')
            ->has('messages.data', 15) // Default pagination limit
            ->where('filters.tags', [$tag->id])
        );
    }
}
