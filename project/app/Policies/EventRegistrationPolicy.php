<?php

namespace App\Policies;

use App\Models\EventRegistration;
use App\Models\User;

/**
 * Les inscriptions suivent l'événement : seuls ceux qui le gèrent les voient.
 */
class EventRegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, EventRegistration $registration): bool
    {
        return (new EventPolicy)->update($user, $registration->event);
    }
}
