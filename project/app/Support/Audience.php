<?php

namespace App\Support;

use App\Models\Church;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ce qu'un fidèle voit selon son église de référence : le national, son
 * district et son église. Sans église choisie : le national seul.
 */
final class Audience
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function relevantTo(Builder $query, ?Church $church, string $scopeColumn = 'scope_type', string $national = 'NATIONAL'): Builder
    {
        $table = $query->getModel()->getTable();

        return $query->where(function (Builder $q) use ($table, $church, $scopeColumn, $national) {
            $q->where("{$table}.{$scopeColumn}", $national);

            if ($church) {
                $q->orWhere(fn (Builder $d) => $d->where("{$table}.{$scopeColumn}", 'DISTRICT')
                    ->where("{$table}.district_id", $church->district_id))
                    ->orWhere(fn (Builder $c) => $c->where("{$table}.{$scopeColumn}", 'CHURCH')
                        ->where("{$table}.church_id", $church->id));
            }
        });
    }

    /**
     * Libellé de la source affiché dans l'application.
     */
    public static function sourceLabel(?string $scope, ?string $districtName, ?string $churchName): string
    {
        return match ($scope) {
            'DISTRICT' => $districtName ?? 'District',
            'CHURCH' => $churchName ?? 'Église locale',
            default => 'Foursquare Côte d\'Ivoire',
        };
    }
}
