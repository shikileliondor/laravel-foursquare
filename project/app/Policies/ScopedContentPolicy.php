<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Perimeter;
use Illuminate\Database\Eloquent\Model;

/**
 * Actualités, événements et notifications : on ne gère que ce qui vise son
 * périmètre, et on ne publie que vers son périmètre.
 */
abstract class ScopedContentPolicy
{
    /** Colonne portant le niveau de publication. */
    protected string $scopeColumn = 'scope_type';

    /** Niveau retenu quand la requête ne le précise pas. */
    protected ?string $defaultScope = null;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Model $model): bool
    {
        return $this->manages($user, $model);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data = []): bool
    {
        return Perimeter::allows(
            $user,
            $data[$this->scopeColumn] ?? $this->defaultScope,
            $data['district_id'] ?? null,
            $data['church_id'] ?? null,
        );
    }

    /**
     * Gérer l'existant et ne le déplacer que dans son périmètre.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, Model $model, array $data = []): bool
    {
        if (! $this->manages($user, $model)) {
            return false;
        }

        return Perimeter::allows(
            $user,
            $data[$this->scopeColumn] ?? $model->getAttribute($this->scopeColumn),
            array_key_exists('district_id', $data) ? $data['district_id'] : $model->getAttribute('district_id'),
            array_key_exists('church_id', $data) ? $data['church_id'] : $model->getAttribute('church_id'),
        );
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->manages($user, $model);
    }

    protected function manages(User $user, Model $model): bool
    {
        return Perimeter::allows(
            $user,
            $model->getAttribute($this->scopeColumn),
            $model->getAttribute('district_id'),
            $model->getAttribute('church_id'),
        );
    }
}
