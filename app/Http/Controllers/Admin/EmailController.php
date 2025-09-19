<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailController extends Controller
{
    /**
     * Display a listing of emails.
     */
    public function index(Request $request): Response
    {
        $query = Message::query()
            ->with(['provider', 'events', 'tags'])
            ->orderBy('created_at', 'desc');

        // Filter by status if provided
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('recipient', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        // Filter by tags if provided
        if ($request->filled('tags') && !empty($request->tags)) {
            $tagIds = is_array($request->tags) ? $request->tags : [$request->tags];
            // Convert to integers to handle string IDs from query parameters
            $tagIds = array_map('intval', $tagIds);
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        $messages = $query->paginate(15)->withQueryString();

        // Get all available tags for the filter
        $availableTags = Tag::orderBy('name')->get();

        return Inertia::render('Admin/Emails/Index', [
            'messages' => $messages,
            'filters' => [
                'status' => $request->status ?? "all",
                'search' => $request->search,
                'tags' => $request->filled('tags') ? array_map('intval', (array) $request->tags) : [],
            ],
            'statusOptions' => [
                ['value' => 'all', 'label' => 'All Statuses'],
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'scheduled', 'label' => 'Scheduled'],
                ['value' => 'sent', 'label' => 'Sent'],
                ['value' => 'failed', 'label' => 'Failed'],
            ],
            'availableTags' => $availableTags,
        ]);
    }

    /**
     * Show the specified email details.
     */
    public function show(Message $message): Response
    {
        $message->load(['provider', 'events', 'tags']);

        return Inertia::render('Admin/Emails/Show', [
            'message' => $message,
        ]);
    }
}
