<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\DailyMeditationResource;
use App\Models\DailyMeditation;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeditationController extends ApiController
{
    public function today(): JsonResponse
    {
        $meditation = DailyMeditation::query()->with('image')->whereDate('publication_date', today())->first();

        return ApiResponse::ok($meditation ? DailyMeditationResource::make($meditation) : null);
    }

    public function index(Request $request): JsonResponse
    {
        $meditations = DailyMeditation::query()
            ->published()
            ->with('image')
            ->orderByDesc('publication_date')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($meditations, DailyMeditationResource::class);
    }

    public function show(string $id): JsonResponse
    {
        $meditation = DailyMeditation::query()->published()->with('image')->findOrFail($id);

        return ApiResponse::ok(DailyMeditationResource::make($meditation));
    }
}
