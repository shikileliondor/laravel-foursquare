<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\DescribesSource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class EventResource extends JsonResource
{
    use DescribesSource;

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
            'church_id' => $this->church_id,
            'source' => $this->source(),
            'category_id' => $this->category_id,
            'category' => CategoryResource::make($this->whenLoaded('category')),
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
            'registration_enabled' => (bool) $this->registration_enabled,
            'registration_open' => $this->registrationOpen(),
            'registrations_count' => $this->whenCounted('registrations'),
            'cover_media_id' => $this->cover_media_id,
            'cover' => MediaResource::make($this->whenLoaded('cover')),
        ];
    }
}
