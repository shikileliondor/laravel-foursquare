<?php

namespace App\Policies;

use App\Models\User;

/**
 * Tout administrateur choisit une catégorie ; seul le niveau national en
 * crée, renomme ou supprime.
 */
abstract class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isNationalAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isNationalAdmin();
    }

    public function delete(User $user): bool
    {
        return $user->isNationalAdmin();
    }
}
