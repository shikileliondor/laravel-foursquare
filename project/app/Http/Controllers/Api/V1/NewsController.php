<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\NewsResource;
use App\Models\News;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $news = News::query()
            ->published()
            ->with('cover')
            ->search($request->string('search')->toString())
            ->when($request->filled('scope'), fn ($q) => $q->where('scope_type', strtoupper($request->string('scope')->toString())))
            ->when($request->filled('district_id'), fn ($q) => $q->where('district_id', $request->string('district_id')->toString()))
            ->when($request->filled('zone_id'), fn ($q) => $q->where('zone_id', $request->string('zone_id')->toString()))
            ->when($request->filled('church_id'), fn ($q) => $q->where('church_id', $request->string('church_id')->toString()))
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($news, NewsResource::class);
    }

    public function show(string $slug): JsonResponse
    {
        $news = News::query()->published()->with('cover')->where('slug', $slug)->first();

        if (! $news) {
            return ApiResponse::error('NEWS_NOT_FOUND', 'Actualité introuvable', 404);
        }

        return ApiResponse::ok(NewsResource::make($news));
    }
}
