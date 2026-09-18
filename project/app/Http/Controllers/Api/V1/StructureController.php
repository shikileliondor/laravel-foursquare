<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ChurchResource;
use App\Http\Resources\DistrictResource;
use App\Http\Resources\ZoneResource;
use App\Models\Church;
use App\Models\District;
use App\Models\Zone;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StructureController extends ApiController
{
    public function districts(Request $request): JsonResponse
    {
        $districts = District::query()
            ->active()
            ->withCount('zones')
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($districts, DistrictResource::class);
    }

    public function zones(Request $request): JsonResponse
    {
        $zones = Zone::query()
            ->active()
            ->with('district')
            ->withCount('churches')
            ->when($request->filled('district_id'), fn ($q) => $q->where('district_id', $request->string('district_id')->toString()))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($zones, ZoneResource::class);
    }

    public function churches(Request $request): JsonResponse
    {
        $churches = Church::query()
            ->active()
            ->with(['zone.district', 'image'])
            ->search($request->string('search')->toString())
            ->when($request->filled('zone_id'), fn ($q) => $q->where('zone_id', $request->string('zone_id')->toString()))
            ->when($request->filled('district_id'), fn ($q) => $q->whereHas('zone', fn ($z) => $z->where('district_id', $request->string('district_id')->toString())))
            ->when($request->filled('commune'), fn ($q) => $q->where('commune', $request->string('commune')->toString()))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($churches, ChurchResource::class);
    }

    public function church(string $slug): JsonResponse
    {
        $church = Church::query()->active()->with(['zone.district', 'image'])->where('slug', $slug)->first();

        if (! $church) {
            return ApiResponse::error('CHURCH_NOT_FOUND', 'Église introuvable', 404);
        }

        return ApiResponse::ok(ChurchResource::make($church));
    }
}
