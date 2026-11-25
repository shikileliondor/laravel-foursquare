<?php

namespace App\Support;

use App\Enums\AdminRole;
use App\Models\Church;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Périmètre d'un administrateur : national, un district ou une église.
 *
 * Seul endroit où sont décrites les règles « qui peut viser quoi » ; les
 * Policies et les requêtes de liste s'appuient dessus.
 */
final class Perimeter
{
    /**
     * L'administrateur peut-il publier (ou cibler) à ce niveau ?
     * `ALL` (audience des notifications) vaut `NATIONAL`.
     */
    public static function allows(User $user, ?string $scope, ?string $districtId, ?string $churchId): bool
    {
        $role = $user->adminRole();
        $scope = $scope === 'ALL' ? 'NATIONAL' : $scope;

        if ($role === null) {
            return false;
        }

        if ($role->isNational()) {
            return true;
        }

        if ($role === AdminRole::DistrictAdmin) {
            return $user->district_id !== null && match ($scope) {
                'DISTRICT' => $districtId === $user->district_id,
                'CHURCH' => $churchId !== null && Church::query()
                    ->whereKey($churchId)->where('district_id', $user->district_id)->exists(),
                default => false,
            };
        }

        return $user->church_id !== null && $scope === 'CHURCH' && $churchId === $user->church_id;
    }

    /**
     * Restreint une liste d'actualités, d'événements ou de notifications à
     * ce que l'administrateur gère.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function restrictContent(Builder $query, User $user, string $scopeColumn): Builder
    {
        $role = $user->adminRole();
        $table = $query->getModel()->getTable();

        if ($role?->isNational()) {
            return $query;
        }

        if ($role === AdminRole::DistrictAdmin && $user->district_id !== null) {
            return $query->where(fn (Builder $q) => $q
                ->where(fn (Builder $d) => $d->where("{$table}.{$scopeColumn}", 'DISTRICT')
                    ->where("{$table}.district_id", $user->district_id))
                ->orWhere(fn (Builder $c) => $c->where("{$table}.{$scopeColumn}", 'CHURCH')
                    ->whereIn("{$table}.church_id", Church::query()->select('id')->where('district_id', $user->district_id))));
        }

        if ($role === AdminRole::ChurchAdmin && $user->church_id !== null) {
            return $query->where("{$table}.{$scopeColumn}", 'CHURCH')
                ->where("{$table}.church_id", $user->church_id);
        }

        // Rôle inconnu ou administrateur sans rattachement : rien.
        return $query->whereRaw('1 = 0');
    }

    /**
     * Ne garde que la cible du niveau choisi : passer de DISTRICT à NATIONAL
     * ne doit pas laisser un district_id orphelin.
     *
     * Une modification partielle garde la cible existante du modèle.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function onlyTargetOf(string $scope, array $data, ?Model $model = null): array
    {
        $current = fn (string $field) => array_key_exists($field, $data) ? $data[$field] : $model?->getAttribute($field);

        $data['district_id'] = $scope === 'DISTRICT' ? $current('district_id') : null;
        $data['church_id'] = $scope === 'CHURCH' ? $current('church_id') : null;

        return $data;
    }

    /**
     * Modules du panel accessibles à cet administrateur.
     *
     * @return list<string>
     */
    public static function modules(User $user): array
    {
        $role = $user->adminRole();

        if ($role === null) {
            return [];
        }

        $modules = ['dashboard', 'news', 'events', 'registrations', 'notifications', 'media', 'churches'];

        if ($role !== AdminRole::ChurchAdmin) {
            $modules[] = 'districts';
        }

        if ($role->isNational()) {
            array_push($modules, 'banners', 'categories', 'devices', 'users');
        }

        if ($role === AdminRole::SuperAdmin) {
            $modules[] = 'settings';
        }

        return $modules;
    }
}
