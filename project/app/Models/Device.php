<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasUuids;

    public const PLATFORMS = ['android', 'ios', 'web'];

    protected $fillable = ['fcm_token', 'platform', 'app_version', 'notifications_enabled', 'last_seen_at'];

    /** @var array<string, string> */
    protected $casts = [
        'notifications_enabled' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    protected $hidden = ['fcm_token'];
}
