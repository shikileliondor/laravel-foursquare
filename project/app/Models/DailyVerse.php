<?php

namespace App\Models;

use App\Models\Concerns\RefreshesPublicCache;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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
}
