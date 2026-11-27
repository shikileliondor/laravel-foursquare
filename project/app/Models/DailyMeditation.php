<?php

namespace App\Models;

use App\Models\Concerns\RefreshesPublicCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class DailyMeditation extends Model
{
    use HasUuids, RefreshesPublicCache;

    protected $fillable = [
        'title', 'content', 'author', 'scripture_reference', 'image_media_id',
        'publication_date', 'notification_created_at', 'created_by',
    ];

    protected $casts = [
        'publication_date' => 'date',
        'notification_created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Media, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    /**
     * La notification push nationale de cette méditation (au plus une).
     *
     * @return HasOne<PushNotification, $this>
     */
    public function notification(): HasOne
    {
        return $this->hasOne(PushNotification::class, 'daily_meditation_id');
    }

    /**
     * Méditations visibles dans l'application : jamais celles programmées.
     *
     * @param  Builder<DailyMeditation>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereDate('publication_date', '<=', today());
    }

    public function isToday(): bool
    {
        return $this->publication_date->isSameDay(today());
    }

    /** Texte brut du contenu, pour les listes et la notification. */
    public function excerpt(int $limit = 200): string
    {
        $text = html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], ' ', $this->content)), ENT_QUOTES | ENT_HTML5);

        return Str::limit(trim((string) preg_replace('/\s+/u', ' ', $text)), $limit);
    }
}
