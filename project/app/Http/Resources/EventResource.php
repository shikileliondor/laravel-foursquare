<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'scope_type' => $this->scope_type,
            'district_id' => $this->district_id,
            'zone_id' => $this->zone_id,
            'church_id' => $this->church_id,
            'organizer_name' => $this->organizer_name,
            'start_at' => $this->start_at->toIso8601String(),
            'end_at' => $this->end_at->toIso8601String(),
            'time_status' => $this->timeStatus(),
            'venue_name' => $this->venue_name,
            'address' => $this->address,
            'commune' => $this->commune,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'contact_phone' => $this->contact_phone,
            'official_whatsapp' => $this->official_whatsapp,
            'priority' => $this->priority,
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            'cover' => MediaResource::make($this->whenLoaded('cover')),
        ];
    }
}
