<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * Single point of storage: swap the disk in config/media.php to move to S3.
     */
    public function store(UploadedFile $file, ?int $userId = null): Media
    {
        $disk = config('media.disk');
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $storedName = Str::uuid()->toString().'.'.$extension;

        $path = $file->storeAs(config('media.folder'), $storedName, ['disk' => $disk]);

        [$width, $height] = @getimagesize($file->getRealPath() ?: '') ?: [null, null];

        return Media::create([
            'original_name' => Str::limit($file->getClientOriginalName(), 200, ''),
            'stored_name' => $storedName,
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'uploaded_by' => $userId,
        ]);
    }

    public function delete(Media $media): void
    {
        Storage::disk(config('media.disk'))->delete($media->path);
        $media->delete();
    }
}
