<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\RefreshesPublicCache;
use App\Support\Audience;
use App\Support\Perimeter;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasSlug, HasUuids, RefreshesPublicCache;

    public const SCOPES = ['NATIONAL', 'DISTRICT', 'CHURCH'];

    public const PRIORITIES = ['NORMAL', 'IMPORTANT', 'URGENT'];

    public const STATUSES = ['DRAFT', 'PUBLISHED', 'CANCELLED', 'ARCHIVED'];

    protected $fillable = [
        'title', 'slug', 'description', 'cover_media_id', 'scope_type',
        'district_id', 'church_id', 'organizer_name', 'start_at', 'end_at',
        'venue_name', 'address', 'commune', 'latitude', 'longitude',
        'contact_phone', 'official_whatsapp', 'priority', 'is_featured', 'status', 'created_by',
        'category_id', 'registration_enabled',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_featured' => 'boolean',
        'registration_enabled' => 'boolean',
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
     * @return BelongsTo<Church, $this>
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * @return BelongsTo<EventCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
    }

    /**
     * @return HasMany<EventRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * Contenu destiné aux fidèles de cette église (national, district, église).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeRelevantTo(Builder $query, ?Church $church): Builder
    {
        return Audience::relevantTo($query, $church);
    }

    /**
     * Inscription possible : activée, événement publié et pas encore terminé.
     */
    public function registrationOpen(): bool
    {
        return $this->registration_enabled && $this->status === 'PUBLISHED' && $this->end_at > now();
    }

    /**
     * Ce que l'administrateur gère, selon son périmètre.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeManageableBy(Builder $query, User $user): Builder
    {
        return Perimeter::restrictContent($query, $user, 'scope_type');
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
