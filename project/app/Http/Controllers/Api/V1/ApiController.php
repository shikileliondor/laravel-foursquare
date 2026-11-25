<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Church;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected const PER_PAGE = 20;

    protected const MAX_PER_PAGE = 100;

    /** Relations nécessaires aux listes publiques d'actualités et d'événements. */
    protected const PUBLIC_CONTENT_RELATIONS = ['cover', 'category', 'district', 'church'];

    protected function perPage(Request $request): int
    {
        return (int) max(1, min(self::MAX_PER_PAGE, $request->integer('per_page', self::PER_PAGE)));
    }

    /**
     * Église de référence choisie dans l'application (`for_church_id`).
     * Inconnue ou inactive : comme si aucune église n'était choisie.
     */
    protected function referenceChurch(Request $request): ?Church
    {
        if (! $request->filled('for_church_id')) {
            return null;
        }

        return Church::query()->active()->whereKey($request->string('for_church_id')->toString())->first();
    }
}
