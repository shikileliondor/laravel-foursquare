<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\StoreEventRequest;
use App\Http\Requests\Api\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\AuditLog;
use App\Models\Event;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $events = Event::query()
            ->with('cover')
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', strtoupper($request->string('status')->toString())))
            ->when($request->filled('scope'), fn ($q) => $q->where('scope_type', strtoupper($request->string('scope')->toString())))
            ->orderByDesc('start_at')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($events, EventResource::class);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = Event::create($request->validated() + ['created_by' => $request->user()->id]);

        AuditLog::record('created', $event);

        return ApiResponse::ok(EventResource::make($event->load('cover')), 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::ok(EventResource::make(Event::with('cover')->findOrFail($id)));
    }

    public function update(UpdateEventRequest $request, string $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $event->update($request->validated());

        AuditLog::record('updated', $event);

        return ApiResponse::ok(EventResource::make($event->fresh('cover')));
    }

    public function destroy(string $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $event->delete();

        AuditLog::record('deleted', $event);

        return ApiResponse::ok(['deleted' => true]);
    }
}
