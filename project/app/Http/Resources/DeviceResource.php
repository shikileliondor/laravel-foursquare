<?php

namespace App\Http\Resources;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Device
 */
class DeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform,
            'app_version' => $this->app_version,
            'notifications_enabled' => $this->notifications_enabled,
            'district_id' => $this->district_id,
            'zone_id' => $this->zone_id,
            'church_id' => $this->church_id,
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
        ];
    }
}
