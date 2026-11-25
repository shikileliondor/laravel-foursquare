<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ChurchResource;
use App\Http\Resources\DistrictResource;
use App\Models\Church;
use App\Models\District;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StructureController extends ApiController
{
    public function districts(Request $request): JsonResponse
    {
        $districts = District::query()
            ->active()
            ->withCount(['churches' => fn ($q) => $q->where('status', 'ACTIVE')])
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($districts, DistrictResource::class);
    }

    public function churches(Request $request): JsonResponse
    {
        $churches = Church::query()
            ->active()
            ->with(['district', 'image'])
            ->search($request->string('search')->toString())
            ->when($request->filled('district_id'), fn ($q) => $q->where('district_id', $request->string('district_id')->toString()))
            ->when($request->filled('commune'), fn ($q) => $q->where('commune', $request->string('commune')->toString()))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($churches, ChurchResource::class);
    }

    public function church(string $slug): JsonResponse
    {
        $church = Church::query()->active()->with(['district', 'image'])->identifiedBy($slug)->first();

        if (! $church) {
            return ApiResponse::error('CHURCH_NOT_FOUND', 'Église introuvable', 404);
        }

        return ApiResponse::ok(ChurchResource::make($church));
    }
}
