<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Paramètres de la plateforme, réservés au super administrateur.
 */
class SettingController extends ApiController
{
    public function show(): JsonResponse
    {
        Gate::authorize('manage-platform');

        return ApiResponse::ok(Setting::known());
    }

    public function update(Request $request): JsonResponse
    {
        Gate::authorize('manage-platform');

        $data = $request->validate(Setting::RULES);

        foreach ($data as $key => $value) {
            Setting::write($key, $value === null ? null : (string) $value);
        }

        AuditLog::record('settings.updated', null, ['keys' => array_keys($data)]);

        return ApiResponse::ok(Setting::known());
    }
}
