<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\DiscoverSectionResource;
use App\Models\DiscoverSection;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DiscoverController extends ApiController
{
    public function index(): JsonResponse
    {
        $sections = DiscoverSection::query()
            ->where('status', 'PUBLISHED')
            ->with(['image', 'illustration'])
            ->orderBy('display_order')
            ->get();

        return ApiResponse::ok(DiscoverSectionResource::collection($sections));
    }

    public function show(string $key): JsonResponse
    {
        $section = DiscoverSection::query()
            ->where('key', $key)
            ->where('status', 'PUBLISHED')
            ->with(['image', 'illustration'])
            ->firstOrFail();

        return ApiResponse::ok(DiscoverSectionResource::make($section));
    }
}
