<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasUuids;

    protected $table = 'media';

    protected $fillable = [
        'original_name', 'stored_name', 'path', 'mime_type', 'file_size', 'width', 'height', 'uploaded_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function url(): string
    {
        return Storage::disk(config('media.disk', 'public'))->url($this->path);
    }
}
