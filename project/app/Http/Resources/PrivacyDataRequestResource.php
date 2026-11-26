<?php

namespace App\Http\Resources;

use App\Models\PrivacyDataRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PrivacyDataRequest
 */
class PrivacyDataRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'type' => $this->type,
            'status' => $this->status,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'event_id' => $this->event_id,
            'event' => $this->whenLoaded('event', fn () => $this->event ? ['id' => $this->event->id, 'title' => $this->event->title] : null),
            'details' => $this->details,
            'privacy_notice_version' => $this->privacy_notice_version,
            'resolution_note' => $this->resolution_note,
            'handler' => $this->whenLoaded('handler', fn () => $this->handler ? ['id' => $this->handler->id, 'name' => $this->handler->name] : null),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
