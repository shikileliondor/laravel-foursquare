<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            return ApiResponse::error('FORBIDDEN', 'Accès refusé', 403);
        }

        if ($user->status !== 'ACTIVE') {
            return ApiResponse::error('ACCOUNT_SUSPENDED', 'Compte suspendu', 403);
        }

        return $next($request);
    }
}
