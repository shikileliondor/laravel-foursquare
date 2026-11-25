<?php

namespace App\Models\Concerns;

use App\Support\PublicCache;

/**
 * Tout enregistrement ou suppression invalide le cache de l'API publique :
 * l'application voit aussitôt ce que le panel vient de publier.
 */
trait RefreshesPublicCache
{
    public static function bootRefreshesPublicCache(): void
    {
        static::saved(fn () => PublicCache::bump());
        static::deleted(fn () => PublicCache::bump());
    }
}
