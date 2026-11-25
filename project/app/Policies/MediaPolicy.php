<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

/**
 * La médiathèque est partagée ; seul le niveau national ou l'auteur de
 * l'envoi peut supprimer une image.
 */
class MediaPolicy
{
    public function delete(User $user, Media $media): bool
    {
        return $user->isNationalAdmin() || $media->uploaded_by === $user->id;
    }
}
