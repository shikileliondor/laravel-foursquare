<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    use HasSlug, HasUuids;

    public const STATUSES = ['ACTIVE', 'INACTIVE'];

    protected $fillable = ['name', 'slug', 'code', 'description', 'status'];

    /**
     * @return HasMany<Zone, $this>
     */
    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
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
