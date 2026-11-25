<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\StoreEventRequest;
use App\Http\Requests\Api\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\AuditLog;
use App\Models\Event;
use App\Support\ApiResponse;
use App\Support\Perimeter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EventController extends ApiController
{
    /** @var list<string> */
    private const WITH = ['cover', 'category', 'district', 'church'];

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Event::class);

        $events = Event::query()
            ->manageableBy($request->user())
            ->with(self::WITH)
            ->withCount('registrations')
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', strtoupper($request->string('status')->toString())))
            ->when($request->filled('scope'), fn ($q) => $q->where('scope_type', strtoupper($request->string('scope')->toString())))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->string('category_id')->toString()))
            ->orderByDesc('start_at')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($events, EventResource::class);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $data = $request->validated();

        Gate::authorize('create', [Event::class, $data]);

        $event = Event::create(Perimeter::onlyTargetOf($data['scope_type'], $data) + ['created_by' => $request->user()->id]);

        AuditLog::record('created', $event);

        return ApiResponse::ok(EventResource::make($event->load(self::WITH)), 201);
    }

    public function show(string $id): JsonResponse
    {
        $event = Event::with(self::WITH)->withCount('registrations')->findOrFail($id);

        Gate::authorize('view', $event);

        return ApiResponse::ok(EventResource::make($event));
    }

    public function update(UpdateEventRequest $request, string $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $data = $request->validated();

        Gate::authorize('update', [$event, $data]);

        $event->update(Perimeter::onlyTargetOf($data['scope_type'] ?? $event->scope_type, $data, $event));

        AuditLog::record('updated', $event);

        return ApiResponse::ok(EventResource::make($event->fresh(self::WITH)));
    }

    public function destroy(string $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        Gate::authorize('delete', $event);

        $event->delete();

        AuditLog::record('deleted', $event);

        return ApiResponse::ok(['deleted' => true]);
    }
}
