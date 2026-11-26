<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\StorePrivacyConsentRequest;
use App\Models\PrivacyConsentEvent;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PrivacyConsentController extends ApiController
{
    public function grant(StorePrivacyConsentRequest $request): JsonResponse
    {
        return $this->record($request, 'GRANTED');
    }

    public function withdraw(StorePrivacyConsentRequest $request): JsonResponse
    {
        return $this->record($request, 'WITHDRAWN');
    }

    private function record(StorePrivacyConsentRequest $request, string $action): JsonResponse
    {
        $data = $request->safe()->except('website');

        $event = PrivacyConsentEvent::query()->firstOrCreate(
            ['action_id' => $data['action_id']],
            $data + [
                'action' => $action,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500) ?: null,
                'occurred_at' => now(),
            ],
        );

        return ApiResponse::ok([
            'id' => $event->id,
            'action_id' => $event->action_id,
            'purpose' => $event->purpose,
            'action' => $event->action,
            'policy_version' => $event->policy_version,
            'recorded_at' => $event->occurred_at->toIso8601String(),
        ], $event->wasRecentlyCreated ? 201 : 200);
    }
}
