<?php

namespace App\Models;

use App\Models\Concerns\RefreshesPublicCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Membre de l'équipe pastorale d'une église (onglet « Mon église »). */
class ChurchLeader extends Model
{
    use HasUuids, RefreshesPublicCache;

    protected $fillable = ['church_id', 'name', 'position', 'description', 'photo_media_id', 'display_order', 'is_published'];

    protected $attributes = ['display_order' => 0, 'is_published' => false];

    protected $casts = ['display_order' => 'integer', 'is_published' => 'boolean'];

    /**
     * @return BelongsTo<Church, $this>
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_media_id');
    }

    /**
     * Responsables des églises que l'administrateur peut gérer.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeManageableBy(Builder $query, User $user): Builder
    {
        return $query->whereIn('church_id', Church::query()->manageableBy($user)->select('id'));
    }
}
