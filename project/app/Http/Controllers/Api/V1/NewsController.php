<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\NewsResource;
use App\Models\News;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends ApiController
{
    /**
     * Filtres de l'application : Toutes (`for_church_id`), National
     * (`scope=NATIONAL`), District (`scope=DISTRICT`), Mon église
     * (`scope=CHURCH`), toujours combinables avec `for_church_id`.
     */
    public function index(Request $request): JsonResponse
    {
        $church = $this->referenceChurch($request);

        $news = News::query()
            ->published()
            ->with(self::PUBLIC_CONTENT_RELATIONS)
            ->when($church, fn ($q) => $q->relevantTo($church))
            ->search($request->string('search')->toString())
            ->when($request->filled('scope'), fn ($q) => $q->where('scope_type', strtoupper($request->string('scope')->toString())))
            ->when($request->filled('district_id'), fn ($q) => $q->where('district_id', $request->string('district_id')->toString()))
            ->when($request->filled('church_id'), fn ($q) => $q->where('church_id', $request->string('church_id')->toString()))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->string('category_id')->toString()))
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($news, NewsResource::class);
    }

    public function show(string $slug): JsonResponse
    {
        $news = News::query()->published()->with(self::PUBLIC_CONTENT_RELATIONS)->identifiedBy($slug)->first();

        if (! $news) {
            return ApiResponse::error('NEWS_NOT_FOUND', 'Actualité introuvable', 404);
        }

        return ApiResponse::ok(NewsResource::make($news));
    }
}
