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
        'district_id', 'church_id',
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
        $query->where('notifications_enabled', true);

        if ($notification->audience === 'ALL') {
            return $query;
        }

        [$column, $target] = match ($notification->audience) {
            'DISTRICT' => ['district_id', $notification->district_id],
            'CHURCH' => ['church_id', $notification->church_id],
            default => [null, null],
        };

        // Cible supprimée ou audience inconnue : personne. where(colonne, null)
        // viserait sinon tous les appareils sans église.
        return $target === null
            ? $query->whereRaw('1 = 0')
            : $query->where($column, $target);
    }
}
