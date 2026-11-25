<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\RefreshesPublicCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    use HasSlug, HasUuids, RefreshesPublicCache;

    public const STATUSES = ['ACTIVE', 'INACTIVE'];

    protected $fillable = ['name', 'slug', 'code', 'description', 'status'];

    /**
     * @return HasMany<Church, $this>
     */
    public function churches(): HasMany
    {
        return $this->hasMany(Church::class);
    }

    /**
     * Districts visibles par l'administrateur : tous au niveau national,
     * sinon le sien (celui de son église pour un administrateur d'église).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeManageableBy(Builder $query, User $user): Builder
    {
        if ($user->isNationalAdmin()) {
            return $query;
        }

        $districtId = $user->perimeterDistrictId();

        return $districtId ? $query->whereKey($districtId) : $query->whereRaw('1 = 0');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }
}
