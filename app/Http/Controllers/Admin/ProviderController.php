<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageProvider;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProviderController extends Controller
{
    /**
     * Display a listing of message providers.
     */
    public function index(Request $request): Response
    {
        $query = MessageProvider::query()
            ->withCount(['messages'])
            ->orderBy('created_at', 'desc');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('provider', 'like', "%{$search}%");
            });
        }

        // Filter by provider type if provided
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('provider', $request->type);
        }

        $providers = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Providers/Index', [
            'providers' => $providers,
            'filters' => [
                'search' => $request->search,
                'type' => $request->type ?? null,
            ],
            'typeOptions' => [
                ['value' => 'all', 'label' => 'All Types'],
                ['value' => 'smtp', 'label' => 'SMTP'],
                ['value' => 'mailgun', 'label' => 'Mailgun'],
                ['value' => 'sendgrid', 'label' => 'SendGrid'],
                ['value' => 'ses', 'label' => 'Amazon SES'],
            ],
        ]);
    }

    /**
     * Show the specified provider details.
     */
    public function show(MessageProvider $provider): Response
    {
        $provider->load(['messages' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return Inertia::render('Admin/Providers/Show', [
            'provider' => $provider,
            'recentMessages' => $provider->messages,
        ]);
    }
}
