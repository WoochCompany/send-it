<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Jobs\ScheduleMessageJob;
use App\Models\Message;
use App\Models\MessageEvent;
use App\Models\Tag;
use App\Models\MessageProvider;
use Carbon\Carbon;

class MessageController extends Controller
{
    public function store(StoreMessageRequest $request): Message
    {
        $data = $request->validatedPayload();

        if (!empty($data['provider_slug'])) {
            $provider = MessageProvider::where('slug', $data['provider_slug'])->first();
            abort_if(!$provider, 400, 'Invalid message provider');
        } else {
            $provider = MessageProvider::default();
        }

        $scheduledAtRequest = isset($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null;

        $message = Message::create([
            'recipient' => $data['recipient'],
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'] ?? null,
            'scheduled_at' => $scheduledAtRequest,
            'scheduled_requested_at' => $scheduledAtRequest ?? now(),
            'status' => 'pending',
            'message_provider_id' => $provider?->id,
        ]);

        // Attach tags if provided
        if (!empty($data['tags']) && is_array($data['tags'])) {
            $tagIds = [];
            foreach ($data['tags'] as $tagName) {
                $tag = Tag::firstOrCreate(['name' => ucfirst($tagName)]);
                $tagIds[] = $tag->id;
            }
            $message->tags()->sync($tagIds);
        }

        // Log event: message_created
        MessageEvent::create([
            'message_id' => $message->id,
            'type' => 'message_created',
            'payload' => [
                'recipient' => $message->recipient,
                'subject' => $message->subject,
                'provider' => $provider?->slug,
            ],
        ]);

        //schedule message
        ScheduleMessageJob::dispatchFromMessage($message);

        return $message;
    }
}
