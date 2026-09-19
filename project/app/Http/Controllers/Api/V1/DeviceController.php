<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\StoreDeviceRequest;
use App\Http\Resources\DeviceResource;
use App\Models\Church;
use App\Models\Device;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DeviceController extends ApiController
{
    /**
     * Register or refresh an FCM device. No member identity in V1.
     */
    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $data = $request->safe()->except('fcm_token');

        // L'appareil ne declare que son eglise : zone et district en decoulent,
        // sans quoi une notification ciblee ne saurait pas qui joindre.
        $church = filled($data['church_id'] ?? null)
            ? Church::with('zone')->find((string) $data['church_id'])
            : null;

        $device = Device::updateOrCreate(
            ['fcm_token' => $request->string('fcm_token')->toString()],
            $data + [
                'zone_id' => $church?->zone_id,
                'district_id' => $church?->zone?->district_id,
                'last_seen_at' => now(),
            ],
        );

        // Sans relecture, les defauts SQL (notifications_enabled) reviennent a null
        // et le client croirait les notifications desactivees.
        return ApiResponse::ok(
            DeviceResource::make($device->refresh()),
            $device->wasRecentlyCreated ? 201 : 200,
        );
    }
}
