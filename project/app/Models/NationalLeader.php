<?php

namespace App\Models;

use App\Models\Concerns\RefreshesPublicCache;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NationalLeader extends Model
{
    use HasUuids, RefreshesPublicCache;

    protected $fillable = ['name', 'position', 'photo_media_id', 'display_order', 'is_published'];

    protected $attributes = ['display_order' => 0, 'is_published' => false];

    protected $casts = ['display_order' => 'integer', 'is_published' => 'boolean'];

    /**
     * @return BelongsTo<Media, $this>
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_media_id');
    }
}
