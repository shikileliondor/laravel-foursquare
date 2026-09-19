<?php

namespace App\Http\Resources;

use App\Models\PushNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PushNotification
 */
class PushNotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'news_id' => $this->news_id,
            'event_id' => $this->event_id,
            'audience' => $this->audience,
            'district_id' => $this->district_id,
            'zone_id' => $this->zone_id,
            'church_id' => $this->church_id,
            'status' => $this->status,
            'retry_count' => $this->retry_count,
            'last_error' => $this->last_error,
            'sent_at' => $this->sent_at?->toIso8601String(),
        ];
    }
}
