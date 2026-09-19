<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    use HasUuids;

    public const PLATFORMS = ['android', 'ios', 'web'];

    protected $fillable = [
        'fcm_token', 'platform', 'app_version', 'notifications_enabled', 'last_seen_at',
        'district_id', 'zone_id', 'church_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'notifications_enabled' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    protected $hidden = ['fcm_token'];

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
     * The devices a push notification is allowed to reach.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForAudience(Builder $query, PushNotification $notification): Builder
    {
        return $query->where('notifications_enabled', true)
            ->when($notification->audience === 'DISTRICT', fn (Builder $q) => $q->where('district_id', $notification->district_id))
            ->when($notification->audience === 'ZONE', fn (Builder $q) => $q->where('zone_id', $notification->zone_id))
            ->when($notification->audience === 'CHURCH', fn (Builder $q) => $q->where('church_id', $notification->church_id));
    }
}
