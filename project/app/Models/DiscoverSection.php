<?php

namespace App\Models;

use App\Models\Concerns\RefreshesPublicCache;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscoverSection extends Model
{
    use HasUuids, RefreshesPublicCache;

    public const STATUSES = ['DRAFT', 'PUBLISHED'];

    protected $fillable = ['content', 'image_media_id', 'illustration_media_id', 'status'];

    /**
     * @return BelongsTo<Media, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function illustration(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'illustration_media_id');
    }
}
