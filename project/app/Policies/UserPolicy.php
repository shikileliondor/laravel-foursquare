<?php

namespace App\Policies;

use App\Enums\AdminRole;
use App\Models\User;

/**
 * Le super administrateur gère tous les comptes ; l'administrateur national
 * gère les administrateurs de district et d'église. Personne ne change son
 * propre rôle, statut ou périmètre, ni ne supprime son propre compte.
 */
class UserPolicy
{
    private const TERRITORIAL = [AdminRole::DistrictAdmin, AdminRole::ChurchAdmin];

    public function viewAny(User $user): bool
    {
        return $user->isNationalAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->is($target) || $this->governs($user, $target);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data = []): bool
    {
        return $this->canAssign($user, $data['role'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, User $target, array $data = []): bool
    {
        if ($user->is($target)) {
            foreach (['role', 'status', 'district_id', 'church_id'] as $field) {
                if (array_key_exists($field, $data) && $data[$field] !== $target->getAttribute($field)) {
                    return false;
                }
            }

            return true;
        }

        return $this->governs($user, $target)
            && (! array_key_exists('role', $data) || $this->canAssign($user, $data['role']));
    }

    public function delete(User $user, User $target): bool
    {
        return ! $user->is($target) && $this->governs($user, $target);
    }

    private function governs(User $user, User $target): bool
    {
        return match ($user->adminRole()) {
            AdminRole::SuperAdmin => true,
            AdminRole::NationalAdmin => in_array($target->adminRole(), self::TERRITORIAL, true),
            default => false,
        };
    }

    private function canAssign(User $user, mixed $role): bool
    {
        $role = AdminRole::tryFrom((string) $role);

        return match ($user->adminRole()) {
            AdminRole::SuperAdmin => $role !== null,
            AdminRole::NationalAdmin => in_array($role, self::TERRITORIAL, true),
            default => false,
        };
    }
}
