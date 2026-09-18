<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasSlug, HasUuids;

    public const SCOPES = ['NATIONAL', 'DISTRICT', 'ZONE', 'CHURCH'];

    public const PRIORITIES = ['NORMAL', 'IMPORTANT', 'URGENT'];

    public const STATUSES = ['DRAFT', 'PUBLISHED', 'CANCELLED', 'ARCHIVED'];

    protected $fillable = [
        'title', 'slug', 'description', 'cover_media_id', 'scope_type',
        'district_id', 'zone_id', 'church_id', 'organizer_name', 'start_at', 'end_at',
        'venue_name', 'address', 'commune', 'latitude', 'longitude',
        'contact_phone', 'official_whatsapp', 'priority', 'is_featured', 'status', 'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_featured' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Computed, never stored: UPCOMING | ONGOING | PAST.
     */
    public function timeStatus(): string
    {
        $now = now();

        return match (true) {
            $this->start_at > $now => 'UPCOMING',
            $this->end_at < $now => 'PAST',
            default => 'ONGOING',
        };
    }

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
        return $query->where('status', 'PUBLISHED');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeTimeStatus(Builder $query, ?string $status): Builder
    {
        $now = now();

        return match (strtoupper((string) $status)) {
            'UPCOMING' => $query->where('start_at', '>', $now),
            'ONGOING' => $query->where('start_at', '<=', $now)->where('end_at', '>=', $now),
            'PAST' => $query->where('end_at', '<', $now),
            default => $query,
        };
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
            ->orWhere('description', $op, $like)
            ->orWhere('venue_name', $op, $like));
    }
}
