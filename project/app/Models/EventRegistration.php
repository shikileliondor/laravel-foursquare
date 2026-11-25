<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inscription d'un participant. Données personnelles : jamais exposées par
 * l'API publique, visibles seulement des administrateurs de l'événement.
 */
class EventRegistration extends Model
{
    use HasUuids;

    protected $fillable = ['event_id', 'full_name', 'phone', 'email', 'church_id', 'church_name'];

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Church, $this>
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
