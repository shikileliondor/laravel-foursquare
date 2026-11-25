<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\StoreChurchRequest;
use App\Http\Requests\Api\UpdateChurchRequest;
use App\Http\Resources\ChurchResource;
use App\Models\AuditLog;
use App\Models\Church;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChurchController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $churches = Church::query()
            ->with(['district', 'image'])
            ->search($request->string('search')->toString())
            ->when($request->filled('district_id'), fn ($q) => $q->where('district_id', $request->string('district_id')->toString()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($churches, ChurchResource::class);
    }

    public function store(StoreChurchRequest $request): JsonResponse
    {
        $church = Church::create($request->validated());

        AuditLog::record('created', $church);

        return ApiResponse::ok(ChurchResource::make($church->load(['district', 'image'])), 201);
    }

    public function show(string $id): JsonResponse
    {
        $church = Church::with(['district', 'image'])->findOrFail($id);

        return ApiResponse::ok(ChurchResource::make($church));
    }

    public function update(UpdateChurchRequest $request, string $id): JsonResponse
    {
        $church = Church::findOrFail($id);
        $church->update($request->validated());

        AuditLog::record('updated', $church);

        return ApiResponse::ok(ChurchResource::make($church->fresh(['district', 'image'])));
    }

    public function destroy(string $id): JsonResponse
    {
        $church = Church::findOrFail($id);
        $church->delete();

        AuditLog::record('deleted', $church);

        return ApiResponse::ok(['deleted' => true]);
    }
}
