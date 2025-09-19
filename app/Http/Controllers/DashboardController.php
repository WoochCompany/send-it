<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageProvider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $now = Carbon::now();
        $today = $now->startOfDay();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();

        // Statistiques générales
        $stats = [
            'emails_today' => Message::whereDate('created_at', $today)->count(),
            'emails_this_week' => Message::where('created_at', '>=', $startOfWeek)->count(),
            'emails_this_month' => Message::where('created_at', '>=', $startOfMonth)->count(),
            'total_emails' => Message::count(),
            'pending_emails' => Message::where('status', 'pending')->count(),
            'scheduled_emails' => Message::where('status', 'scheduled')->count(),
            'total_providers' => MessageProvider::count(),
            'default_provider' => MessageProvider::where('is_default', true)->first()?->name ?? 'None',
        ];

        // Taux de réussite
        $totalSent = Message::whereIn('status', ['sent', 'failed'])->count();
        $successfulSent = Message::where('status', 'sent')->count();
        $stats['success_rate'] = $totalSent > 0 ? round(($successfulSent / $totalSent) * 100, 1) : 0;

        // Emails programmés dans les 60 prochaines minutes par provider avec timeline
        $next60Minutes = $now->copy()->addMinutes(60);
        $scheduledByProvider = Message::query()
            ->join('message_providers', 'messages.message_provider_id', '=', 'message_providers.id')
            ->where('status', 'scheduled')
            ->whereBetween('scheduled_at', [$now, $next60Minutes])
            ->select(
                'message_providers.name as provider_name',
                'message_providers.messages_per_minute as rate_limit',
                'scheduled_at',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('message_providers.id', 'message_providers.name', 'message_providers.messages_per_minute', 'scheduled_at')
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy('provider_name')
            ->map(function ($providerMessages, $providerName) use ($now) {
                $provider = $providerMessages->first();
                $timeSlots = [];

                // Créer des slots de 5 minutes pour les 60 prochaines minutes
                for ($i = 0; $i < 12; $i++) {
                    $slotStart = $now->copy()->addMinutes($i * 5);
                    $slotEnd = $slotStart->copy()->addMinutes(5);
                    $slotLabel = $slotStart->format('H:i');

                    $count = $providerMessages->filter(function ($msg) use ($slotStart, $slotEnd) {
                        $scheduledAt = \Carbon\Carbon::parse($msg->scheduled_at);
                        return $scheduledAt->between($slotStart, $slotEnd);
                    })->sum('count');

                    $timeSlots[] = [
                        'time' => $slotLabel,
                        'count' => $count,
                        'timestamp' => $slotStart->toISOString()
                    ];
                }

                return [
                    'provider_name' => $providerName,
                    'rate_limit' => $provider->rate_limit,
                    'time_slots' => $timeSlots
                ];
            })
            ->values();

        // Répartition par statut
        $statusDistribution = Message::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        // Messages par jour (7 derniers jours)
        $dailyMessages = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $count = Message::whereDate('created_at', $date)->count();
            $dailyMessages[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('M j'),
                'count' => $count
            ];
        }

        // Utilisation des providers
        $providerUsage = Message::query()
            ->join('message_providers', 'messages.message_provider_id', '=', 'message_providers.id')
            ->select('message_providers.name as provider_name', DB::raw('COUNT(*) as count'))
            ->groupBy('message_providers.id', 'message_providers.name')
            ->orderByDesc('count')
            ->get();

        // Messages récents
        $recentMessages = Message::query()
            ->with('provider')
            ->latest()
            ->limit(5)
            ->get();

        // Erreurs récentes
        $recentErrors = Message::query()
            ->with('provider')
            ->where('status', 'failed')
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'charts' => [
                'scheduled_by_provider' => $scheduledByProvider,
                'status_distribution' => $statusDistribution,
                'daily_messages' => $dailyMessages,
                'provider_usage' => $providerUsage,
            ],
            'recent_messages' => $recentMessages,
            'recent_errors' => $recentErrors,
        ]);
    }
}
