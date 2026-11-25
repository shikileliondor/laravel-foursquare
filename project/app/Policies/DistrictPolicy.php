<?php

namespace App\Policies;

use App\Enums\AdminRole;
use App\Models\District;
use App\Models\User;

/**
 * Créer, supprimer ou (dés)activer un district : niveau national. Un
 * administrateur de district met à jour les informations du sien.
 */
class DistrictPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, District $district): bool
    {
        return $user->isNationalAdmin() || $district->id === $user->perimeterDistrictId();
    }

    public function create(User $user): bool
    {
        return $user->isNationalAdmin();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, District $district, array $data = []): bool
    {
        if ($user->isNationalAdmin()) {
            return true;
        }

        $changesStatus = array_key_exists('status', $data) && $data['status'] !== $district->status;

        return $user->adminRole() === AdminRole::DistrictAdmin
            && $district->id === $user->district_id
            && ! $changesStatus;
    }

    public function delete(User $user, District $district): bool
    {
        return $user->isNationalAdmin();
    }
}
