<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\StoreDeviceRequest;
use App\Http\Resources\DeviceResource;
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
        $device = Device::updateOrCreate(
            ['fcm_token' => $request->string('fcm_token')->toString()],
            $request->safe()->except('fcm_token') + ['last_seen_at' => now()],
        );

        return ApiResponse::ok(DeviceResource::make($device), $device->wasRecentlyCreated ? 201 : 200);
    }
}
