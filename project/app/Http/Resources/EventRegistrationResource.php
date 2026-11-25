<?php

namespace App\Http\Resources;

use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EventRegistration
 */
class EventRegistrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'event' => $this->whenLoaded('event', fn () => ['id' => $this->event->id, 'title' => $this->event->title]),
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'church_id' => $this->church_id,
            'church_name' => $this->churchLabel(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function churchLabel(): ?string
    {
        return $this->relationLoaded('church') && $this->church ? $this->church->name : $this->church_name;
    }
}
