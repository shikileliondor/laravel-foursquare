<?php

namespace App\Services;

use App\Models\Church;
use App\Models\Device;

class DeviceService
{
    /**
     * Register or refresh an FCM device from the public API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): Device
    {
        $token = (string) $data['fcm_token'];
        unset($data['fcm_token']);

        $church = filled($data['church_id'] ?? null)
            ? Church::with('zone')->find((string) $data['church_id'])
            : null;

        return Device::updateOrCreate(
            ['fcm_token' => $token],
            $data + [
                'zone_id' => $church?->zone_id,
                'district_id' => $church?->zone?->district_id,
                'notifications_enabled' => $data['notifications_enabled'] ?? true,
                'last_seen_at' => now(),
            ],
        );
    }
}
