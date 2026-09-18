<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'action', 'entity_type', 'entity_id', 'ip_address', 'user_agent', 'metadata', 'created_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Record an action. Never pass secrets in $metadata.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function record(string $action, ?Model $entity = null, array $metadata = []): self
    {
        $request = request();

        return static::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id' => $entity?->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }
}
