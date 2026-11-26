<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\DailyVerseResource;
use App\Models\DailyVerse;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerseController extends ApiController
{
    public function today(): JsonResponse
    {
        $verse = DailyVerse::query()->whereDate('publication_date', today())->first();

        return ApiResponse::ok($verse ? DailyVerseResource::make($verse) : null);
    }

    public function index(Request $request): JsonResponse
    {
        $verses = DailyVerse::query()
            ->whereDate('publication_date', '<=', today())
            ->orderByDesc('publication_date')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($verses, DailyVerseResource::class);
    }
}
