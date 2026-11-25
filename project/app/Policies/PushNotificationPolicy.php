<?php

namespace App\Policies;

/**
 * L'audience `ALL` est le niveau national : réservée au périmètre national.
 */
class PushNotificationPolicy extends ScopedContentPolicy
{
    protected string $scopeColumn = 'audience';

    protected ?string $defaultScope = 'ALL';
}
