<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyConsentEvent extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    public const PURPOSES = [
        'CHURCH_PERSONALIZATION',
        'PUSH_NOTIFICATIONS',
        'EVENT_CHURCH_ASSOCIATION',
    ];

    public const ACTIONS = ['GRANTED', 'WITHDRAWN'];

    public const PLATFORMS = ['android', 'ios', 'web'];

    protected $fillable = [
        'action_id', 'subject_id', 'purpose', 'action', 'policy_version', 'platform',
        'church_id', 'event_id', 'ip_address', 'user_agent', 'occurred_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'occurred_at' => 'immutable_datetime',
    ];

    /** @return BelongsTo<Church, $this> */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
