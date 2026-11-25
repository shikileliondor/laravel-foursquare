<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Church;
use App\Models\District;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\News;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Statistiques du tableau de bord, toujours limitées au périmètre de
 * l'administrateur connecté.
 */
class DashboardController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $news = News::query()->manageableBy($user);
        $events = Event::query()->manageableBy($user);

        $latest = (clone $news)->latest()->limit(5)->get(['id', 'title', 'scope_type', 'status', 'created_at'])
            ->map(fn (News $n) => ['type' => 'news', 'id' => $n->id, 'title' => $n->title, 'scope_type' => $n->scope_type, 'status' => $n->status, 'created_at' => $n->created_at?->toIso8601String()])
            ->concat((clone $events)->latest()->limit(5)->get(['id', 'title', 'scope_type', 'status', 'created_at'])
                ->map(fn (Event $e) => ['type' => 'event', 'id' => $e->id, 'title' => $e->title, 'scope_type' => $e->scope_type, 'status' => $e->status, 'created_at' => $e->created_at?->toIso8601String()]))
            ->sortByDesc('created_at')
            ->take(5)
            ->values();

        return ApiResponse::ok([
            'districts' => District::query()->manageableBy($user)->count(),
            'churches' => Church::query()->manageableBy($user)->count(),
            'active_churches' => Church::query()->manageableBy($user)->where('status', 'ACTIVE')->count(),
            'news' => (clone $news)->count(),
            'events' => (clone $events)->count(),
            'registrations' => EventRegistration::query()
                ->whereIn('event_id', Event::query()->manageableBy($user)->select('events.id'))
                ->count(),
            'latest' => $latest,
        ]);
    }
}
