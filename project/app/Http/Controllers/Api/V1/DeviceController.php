<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\StoreDeviceRequest;
use App\Http\Resources\DeviceResource;
use App\Services\DeviceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DeviceController extends ApiController
{
    /**
     * Register or refresh an FCM device. No member identity in V1.
     */
    public function store(StoreDeviceRequest $request, DeviceService $devices): JsonResponse
    {
        $device = $devices->register($request->validated());

        // Sans relecture, les defauts SQL (notifications_enabled) reviennent a null
        // et le client croirait les notifications desactivees.
        return ApiResponse::ok(
            DeviceResource::make($device->refresh()),
            $device->wasRecentlyCreated ? 201 : 200,
        );
    }
}
