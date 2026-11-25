<?php

namespace App\Http\Resources;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Banner
 */
class BannerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'button_text' => $this->button_text,
            'link_type' => $this->link_type,
            'news_id' => $this->news_id,
            'event_id' => $this->event_id,
            'church_id' => $this->church_id,
            'external_url' => $this->external_url,
            'display_order' => $this->display_order,
            'is_active' => $this->is_active,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'media_id' => $this->media_id,
            'media' => MediaResource::make($this->whenLoaded('media')),
        ];
    }
}
