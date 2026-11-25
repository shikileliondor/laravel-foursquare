<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Comportement commun aux catégories d'actualités et d'événements.
 */
trait IsCategory
{
    use HasSlug;

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function initializeIsCategory(): void
    {
        $this->mergeFillable(['name', 'slug', 'display_order', 'is_active']);
        $this->mergeCasts(['is_active' => 'boolean', 'display_order' => 'integer']);
    }
}
