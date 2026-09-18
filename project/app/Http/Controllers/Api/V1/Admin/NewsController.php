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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $news = News::query()
            ->with('cover')
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', strtoupper($request->string('status')->toString())))
            ->when($request->filled('scope'), fn ($q) => $q->where('scope_type', strtoupper($request->string('scope')->toString())))
            ->latest()
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($news, NewsResource::class);
    }

    public function store(StoreNewsRequest $request): JsonResponse
    {
        $news = News::create($this->payload($request->validated(), $request));

        AuditLog::record('created', $news);

        return ApiResponse::ok(NewsResource::make($news->load('cover')), 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::ok(NewsResource::make(News::with('cover')->findOrFail($id)));
    }

    public function update(UpdateNewsRequest $request, string $id): JsonResponse
    {
        $news = News::findOrFail($id);
        $news->update($this->payload($request->validated(), $request));

        AuditLog::record('updated', $news);

        return ApiResponse::ok(NewsResource::make($news->fresh('cover')));
    }

    public function destroy(string $id): JsonResponse
    {
        $news = News::findOrFail($id);
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
