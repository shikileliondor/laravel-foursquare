<?php

namespace App\Policies;

use App\Enums\AdminRole;
use App\Models\Church;
use App\Models\User;

/**
 * National : toutes les églises. District : les églises de son district,
 * sans pouvoir les déplacer ailleurs. Église : sa fiche, sans changer son
 * district ni son statut.
 */
class ChurchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Church $church): bool
    {
        return $this->manages($user, $church);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data = []): bool
    {
        if ($user->isNationalAdmin()) {
            return true;
        }

        return $user->adminRole() === AdminRole::DistrictAdmin
            && $user->district_id !== null
            && ($data['district_id'] ?? null) === $user->district_id;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, Church $church, array $data = []): bool
    {
        if (! $this->manages($user, $church)) {
            return false;
        }

        $moves = array_key_exists('district_id', $data) && $data['district_id'] !== $church->district_id;
        $changesStatus = array_key_exists('status', $data) && $data['status'] !== $church->status;

        return match ($user->adminRole()) {
            AdminRole::SuperAdmin, AdminRole::NationalAdmin => true,
            AdminRole::DistrictAdmin => ! $moves,
            AdminRole::ChurchAdmin => ! $moves && ! $changesStatus,
            default => false,
        };
    }

    public function delete(User $user, Church $church): bool
    {
        return $user->isNationalAdmin();
    }

    private function manages(User $user, Church $church): bool
    {
        return match ($user->adminRole()) {
            AdminRole::SuperAdmin, AdminRole::NationalAdmin => true,
            AdminRole::DistrictAdmin => $user->district_id !== null && $church->district_id === $user->district_id,
            AdminRole::ChurchAdmin => $user->church_id !== null && $church->id === $user->church_id,
            default => false,
        };
    }
}
