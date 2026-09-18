<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email')->toString())->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return ApiResponse::error('INVALID_CREDENTIALS', 'Identifiants invalides', 401);
        }

        if ($user->status !== 'ACTIVE') {
            return ApiResponse::error('ACCOUNT_SUSPENDED', 'Compte suspendu', 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $token = $user->createToken($request->string('device_name')->toString() ?: 'api')->plainTextToken;

        AuditLog::record('auth.login', $user);

        return ApiResponse::ok([
            'user' => UserResource::make($user)->resolve(),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        AuditLog::record('auth.logout', $request->user());

        return ApiResponse::ok(['message' => 'Déconnecté']);
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::ok(UserResource::make($request->user()));
    }
}
