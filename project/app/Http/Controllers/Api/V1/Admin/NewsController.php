<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\StoreNewsRequest;
use App\Http\Requests\Api\UpdateNewsRequest;
use App\Http\Resources\NewsResource;
use App\Models\AuditLog;
use App\Models\News;
use App\Services\HtmlSanitizer;
use App\Support\ApiResponse;
use App\Support\Perimeter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NewsController extends ApiController
{
    /** @var list<string> */
    private const WITH = ['cover', 'category', 'district', 'church'];

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', News::class);

        $news = News::query()
            ->manageableBy($request->user())
            ->with(self::WITH)
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', strtoupper($request->string('status')->toString())))
            ->when($request->filled('scope'), fn ($q) => $q->where('scope_type', strtoupper($request->string('scope')->toString())))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->string('category_id')->toString()))
            ->latest()
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($news, NewsResource::class);
    }

    public function store(StoreNewsRequest $request): JsonResponse
    {
        $data = $request->validated();

        Gate::authorize('create', [News::class, $data]);

        $news = News::create($this->payload(Perimeter::onlyTargetOf($data['scope_type'], $data), $request));

        AuditLog::record('created', $news);

        return ApiResponse::ok(NewsResource::make($news->load(self::WITH)), 201);
    }

    public function show(string $id): JsonResponse
    {
        $news = News::with(self::WITH)->findOrFail($id);

        Gate::authorize('view', $news);

        return ApiResponse::ok(NewsResource::make($news));
    }

    public function update(UpdateNewsRequest $request, string $id): JsonResponse
    {
        $news = News::findOrFail($id);
        $data = $request->validated();

        Gate::authorize('update', [$news, $data]);

        $data = Perimeter::onlyTargetOf($data['scope_type'] ?? $news->scope_type, $data, $news);
        $news->update($this->payload($data, $request));

        AuditLog::record('updated', $news);

        return ApiResponse::ok(NewsResource::make($news->fresh(self::WITH)));
    }

    public function destroy(string $id): JsonResponse
    {
        $news = News::findOrFail($id);

        Gate::authorize('delete', $news);

        $news->delete();

        AuditLog::record('deleted', $news);

        return ApiResponse::ok(['deleted' => true]);
    }

    /**
     * Sanitize rich text, stamp the author, publish on demand.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data, Request $request): array
    {
        if (isset($data['content'])) {
            $data['content'] = HtmlSanitizer::clean($data['content']);
        }

        if (($data['status'] ?? null) === 'PUBLISHED' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $data['created_by'] ??= $request->user()->id;

        return $data;
    }
}
