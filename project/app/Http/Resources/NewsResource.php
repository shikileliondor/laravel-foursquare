<?php

namespace App\Http\Resources;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin News
 */
class NewsResource extends JsonResource
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
            'excerpt' => $this->excerpt,
            'content' => $this->when($request->routeIs('*.show') || $request->boolean('full'), $this->content),
            'scope_type' => $this->scope_type,
            'district_id' => $this->district_id,
            'zone_id' => $this->zone_id,
            'church_id' => $this->church_id,
            'priority' => $this->priority,
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'cover' => MediaResource::make($this->whenLoaded('cover')),
        ];
    }
}
