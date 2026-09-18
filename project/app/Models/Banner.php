<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banner extends Model
{
    use HasUuids;

    public const LINK_TYPES = ['NONE', 'NEWS', 'EVENT', 'CHURCH', 'EXTERNAL'];

    protected $fillable = [
        'title', 'subtitle', 'media_id', 'button_text', 'link_type',
        'news_id', 'event_id', 'church_id', 'external_url',
        'display_order', 'is_active', 'starts_at', 'ends_at', 'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisible(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }
}
