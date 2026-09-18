<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\StoreMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\AuditLog;
use App\Models\Media;
use App\Services\MediaService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends ApiController
{
    public function __construct(private readonly MediaService $media) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::paginated(
            Media::query()->latest()->paginate($this->perPage($request)),
            MediaResource::class,
        );
    }

    public function store(StoreMediaRequest $request): JsonResponse
    {
        $media = $this->media->store($request->file('file'), $request->user()->id);

        AuditLog::record('uploaded', $media, ['mime_type' => $media->mime_type, 'file_size' => $media->file_size]);

        return ApiResponse::ok(MediaResource::make($media), 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::ok(MediaResource::make(Media::findOrFail($id)));
    }

    public function destroy(string $id): JsonResponse
    {
        $media = Media::findOrFail($id);
        $this->media->delete($media);

        AuditLog::record('deleted', $media);

        return ApiResponse::ok(['deleted' => true]);
    }
}
