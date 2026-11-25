<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\PublicNotificationResource;
use App\Models\PushNotification;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    /**
     * Historique des notifications envoyées : nationales, plus celles du
     * district et de l'église de référence (`for_church_id`).
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = PushNotification::query()
            ->where('status', 'SENT')
            ->relevantTo($this->referenceChurch($request))
            ->with(['news:id,slug', 'event:id,slug'])
            ->orderByDesc('sent_at')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($notifications, PublicNotificationResource::class);
    }
}
