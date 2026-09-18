<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PushNotification extends Model
{
    use HasUuids;

    public const TYPES = ['GENERAL', 'NEWS', 'EVENT'];

    public const AUDIENCES = ['ALL', 'DISTRICT', 'ZONE', 'CHURCH'];

    public const STATUSES = ['DRAFT', 'PENDING', 'PROCESSING', 'SENT', 'FAILED'];

    protected $table = 'push_notifications';

    protected $fillable = [
        'title', 'body', 'type', 'news_id', 'event_id', 'audience',
        'district_id', 'zone_id', 'church_id', 'status', 'retry_count',
        'last_error', 'sent_at', 'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'retry_count' => 'integer',
        'sent_at' => 'datetime',
    ];
}
