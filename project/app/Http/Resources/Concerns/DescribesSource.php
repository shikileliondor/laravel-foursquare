<?php

namespace App\Http\Resources\Concerns;

use App\Support\Audience;

/**
 * Niveau et libellé de la source d'une publication, pour distinguer
 * visuellement National / District / Église locale dans l'application.
 */
trait DescribesSource
{
    /**
     * @return array{scope: string, label: string}
     */
    protected function source(): array
    {
        $model = $this->resource;

        return [
            'scope' => (string) $model->scope_type,
            'label' => Audience::sourceLabel(
                $model->scope_type,
                $model->relationLoaded('district') ? $model->district?->name : null,
                $model->relationLoaded('church') ? $model->church?->name : null,
            ),
        ];
    }
}
