<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class News extends Model
{
    use HasSlug, HasUuids;

    public const SCOPES = ['NATIONAL', 'DISTRICT', 'ZONE', 'CHURCH'];

    public const PRIORITIES = ['NORMAL', 'IMPORTANT', 'URGENT'];

    public const STATUSES = ['DRAFT', 'PUBLISHED', 'ARCHIVED'];

    protected $table = 'news';

    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'cover_media_id', 'scope_type',
        'district_id', 'zone_id', 'church_id', 'priority', 'is_featured',
        'status', 'published_at', 'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Media, $this>
     */
    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    /**
     * @return BelongsTo<District, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * @return BelongsTo<Zone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * @return BelongsTo<Church, $this>
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'PUBLISHED')
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.$term.'%';
        $op = Sql::like();

        return $query->where(fn (Builder $q) => $q->where('title', $op, $like)
            ->orWhere('excerpt', $op, $like)
            ->orWhere('content', $op, $like));
    }
}
