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

        $attributes = $data + [
            'notifications_enabled' => $data['notifications_enabled'] ?? true,
            'last_seen_at' => now(),
        ];

        // Le district suit l'église. Sans church_id dans la requête, l'appareil
        // garde son église et son district ; church_id à null les efface.
        if (array_key_exists('church_id', $data)) {
            $attributes['district_id'] = filled($data['church_id'])
                ? Church::query()->whereKey((string) $data['church_id'])->value('district_id')
                : null;
        }

        return Device::updateOrCreate(['fcm_token' => $token], $attributes);
    }
}
