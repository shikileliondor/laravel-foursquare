<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyDataRequest extends Model
{
    use HasUuids;

    public const TYPES = ['ACCESS', 'RECTIFICATION', 'DELETION', 'OPPOSITION', 'PORTABILITY'];

    public const STATUSES = ['RECEIVED', 'VERIFYING', 'IN_PROGRESS', 'COMPLETED', 'REJECTED'];

    protected $fillable = [
        'reference', 'type', 'full_name', 'phone', 'email', 'event_id', 'details',
        'privacy_notice_version', 'status', 'ip_address', 'user_agent', 'completed_at',
        'resolution_note', 'handled_by',
    ];

    /** Statuts qui clôturent la demande. */
    public const CLOSED_STATUSES = ['COMPLETED', 'REJECTED'];

    /** @var array<string, string> */
    protected $casts = [
        'completed_at' => 'immutable_datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
