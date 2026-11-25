<?php

namespace App\Http\Middleware;

use App\Support\PublicCache;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sert les lectures publiques depuis PublicCache. Placé avant la limitation de
 * débit (bootstrap/app.php) : une réponse en cache ne coûte ni requête SQL ni
 * compteur, ce qui absorbe les pics d'ouverture de l'application.
 */
class CachePublicResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        // Les recherches libres varient trop pour qu'un cache serve.
        if (! $request->isMethod('GET') || $request->filled('search')) {
            return $next($request);
        }

        $store = PublicCache::store();
        $key = PublicCache::key($request);
        $version = PublicCache::version();
        $cached = $store->get($key);

        if (is_array($cached) && ($cached['version'] ?? null) === $version && is_string($cached['body'] ?? null)) {
            return new JsonResponse($cached['body'], 200, ['X-Cache' => 'HIT'], json: true);
        }

        $response = $next($request);
        $body = $response->getContent();

        if ($response instanceof JsonResponse && $response->getStatusCode() === 200 && is_string($body)) {
            $store->put($key, ['version' => $version, 'body' => $body], PublicCache::ttl());
            $response->headers->set('X-Cache', 'MISS');
        }

        return $response;
    }
}
