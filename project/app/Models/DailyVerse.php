<?php

namespace App\Models;

use App\Models\Concerns\RefreshesPublicCache;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DailyVerse extends Model
{
    use HasUuids, RefreshesPublicCache;

    protected $fillable = [
        'text', 'reference', 'bible_version', 'publication_date',
        'notification_created_at', 'created_by',
    ];

    protected $casts = [
        'publication_date' => 'date',
        'notification_created_at' => 'datetime',
    ];

    /**
     * La notification push nationale de ce verset (au plus une).
     *
     * @return HasOne<PushNotification, $this>
     */
    public function notification(): HasOne
    {
        return $this->hasOne(PushNotification::class, 'daily_verse_id');
    }

    public function isToday(): bool
    {
        return $this->publication_date->isSameDay(today());
    }
}
