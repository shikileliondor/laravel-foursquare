<?php

namespace App\Support;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cache court des réponses de l'API publique.
 *
 * Chaque modification d'un contenu public change la version : les réponses
 * mémorisées sont ignorées et recalculées à la requête suivante. Une même URL
 * réutilise toujours la même clé, le cache ne grossit donc pas avec le temps.
 */
class PublicCache
{
    private const VERSION_KEY = 'public-api:version';

    public static function store(): Repository
    {
        $store = config('foursquare.public_cache.store');

        return Cache::store(is_string($store) && $store !== '' ? $store : null);
    }

    public static function ttl(): int
    {
        return config()->integer('foursquare.public_cache.ttl', 60);
    }

    public static function version(): string
    {
        return (string) static::store()->rememberForever(self::VERSION_KEY, fn (): string => Str::random(16));
    }

    /**
     * Au mieux : un cache impossible à écrire (droits du dossier, base) ne doit
     * jamais faire échouer l'enregistrement d'un contenu dans le panel. Au pire,
     * l'ancienne réponse reste servie jusqu'à expiration (TTL).
     */
    public static function bump(): void
    {
        try {
            static::store()->forever(self::VERSION_KEY, Str::random(16));
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Les URL d'images de la réponse dépendent de l'hôte appelé : il fait
     * partie de la clé, avec le chemin et les paramètres triés.
     */
    public static function key(Request $request): string
    {
        $query = $request->query->all();
        ksort($query);

        return 'public-api:'.sha1($request->getSchemeAndHttpHost().$request->getPathInfo().'?'.http_build_query($query));
    }
}
