<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $events = Event::query()
            ->published()
            ->with('cover')
            ->search($request->string('search')->toString())
            ->timeStatus($request->string('status')->toString())
            ->when($request->filled('scope'), fn ($q) => $q->where('scope_type', strtoupper($request->string('scope')->toString())))
            ->when($request->filled('district_id'), fn ($q) => $q->where('district_id', $request->string('district_id')->toString()))
            ->when($request->filled('zone_id'), fn ($q) => $q->where('zone_id', $request->string('zone_id')->toString()))
            ->when($request->filled('church_id'), fn ($q) => $q->where('church_id', $request->string('church_id')->toString()))
            ->orderBy('start_at')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($events, EventResource::class);
    }

    public function show(string $slug): JsonResponse
    {
        $event = Event::query()->published()->with('cover')->where('slug', $slug)->first();

        if (! $event) {
            return ApiResponse::error('EVENT_NOT_FOUND', 'Événement introuvable', 404);
        }

        return ApiResponse::ok(EventResource::make($event));
    }
}
