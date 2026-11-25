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
}
