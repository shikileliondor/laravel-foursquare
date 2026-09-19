<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Media;
use App\Support\ApiResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class MediaController extends ApiController
{
    public function file(string $id): Response|SymfonyResponse
    {
        $media = Media::find($id);

        if (! $media) {
            return ApiResponse::error('MEDIA_NOT_FOUND', 'Media introuvable', 404);
        }

        $disk = Storage::disk(config('media.disk', 'public'));

        if (! $disk->exists($media->path)) {
            return ApiResponse::error('MEDIA_FILE_NOT_FOUND', 'Fichier media introuvable', 404);
        }

        $headers = [
            'Content-Type' => $media->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ];

        $size = $disk->size($media->path) ?: $media->file_size;
        if ($size) {
            $headers['Content-Length'] = (string) $size;
        }

        return response($disk->get($media->path), 200, $headers);
    }
}
