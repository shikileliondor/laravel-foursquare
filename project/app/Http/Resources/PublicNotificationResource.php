<?php

namespace App\Http\Resources;

use App\Models\PushNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PushNotification
 */
class PublicNotificationResource extends JsonResource
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
            'audience' => $this->audience,
            'news_id' => $this->news_id,
            'news_slug' => $this->whenLoaded('news', fn () => $this->news?->slug),
            'event_id' => $this->event_id,
            'event_slug' => $this->whenLoaded('event', fn () => $this->event?->slug),
            'daily_verse_id' => $this->daily_verse_id,
            'sent_at' => $this->sent_at?->toIso8601String(),
        ];
    }
}
