<?php

namespace App\Models;

use App\Models\Concerns\RefreshesPublicCache;
use App\Support\Audience;
use App\Support\Perimeter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotification extends Model
{
    use HasUuids, RefreshesPublicCache;

    public const TYPES = ['GENERAL', 'NEWS', 'EVENT'];

    public const AUDIENCES = ['ALL', 'DISTRICT', 'CHURCH'];

    public const STATUSES = ['DRAFT', 'PENDING', 'PROCESSING', 'SENT', 'FAILED'];

    protected $table = 'push_notifications';

    protected $fillable = [
        'title', 'body', 'type', 'news_id', 'event_id', 'audience',
        'district_id', 'church_id', 'status', 'retry_count',
        'delivered_count', 'pruned_count', 'failed_count',
        'last_error', 'sent_at', 'created_by',
    ];

    /**
     * Les defauts SQL ne sont pas relus apres un INSERT : sans ces valeurs,
     * l'API renverrait `null` pour des compteurs entiers a la creation.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'retry_count' => 0,
        'delivered_count' => 0,
        'pruned_count' => 0,
        'failed_count' => 0,
    ];

    /** @var array<string, string> */
    protected $casts = [
        'retry_count' => 'integer',
        'delivered_count' => 'integer',
        'pruned_count' => 'integer',
        'failed_count' => 'integer',
        'sent_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<News, $this>
     */
    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Historique visible des fidèles de cette église.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeRelevantTo(Builder $query, ?Church $church): Builder
    {
        return Audience::relevantTo($query, $church, 'audience', 'ALL');
    }

    /**
     * Ce que l'administrateur gère, selon son périmètre.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeManageableBy(Builder $query, User $user): Builder
    {
        return Perimeter::restrictContent($query, $user, 'audience');
    }
}
