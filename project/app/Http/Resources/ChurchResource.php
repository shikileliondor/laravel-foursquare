<?php

namespace App\Http\Resources;

use App\Models\Church;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Church
 */
class ChurchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'zone_id' => $this->zone_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'code' => $this->code,
            'pastor_name' => $this->pastor_name,
            'address' => $this->address,
            'commune' => $this->commune,
            'quartier' => $this->quartier,
            'secretariat_phone' => $this->secretariat_phone,
            'official_whatsapp' => $this->official_whatsapp,
            'official_email' => $this->official_email,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'main_service_day' => $this->main_service_day,
            'main_service_time' => $this->main_service_time,
            'description' => $this->description,
            'status' => $this->status,
            'image_media_id' => $this->image_media_id,
            'image' => MediaResource::make($this->whenLoaded('image')),
            'zone' => ZoneResource::make($this->whenLoaded('zone')),
        ];
    }
}
