<?php

namespace App\Http\Resources;

use App\Models\DiscoverSection;
use App\Models\NationalLeader;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DiscoverSection
 */
class DiscoverSectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'title' => $this->title,
            'content' => $this->content,
            'image_media_id' => $this->image_media_id,
            'image' => MediaResource::make($this->whenLoaded('image')),
            'illustration_media_id' => $this->illustration_media_id,
            'illustration' => MediaResource::make($this->whenLoaded('illustration')),
            'display_order' => $this->display_order,
            'status' => $this->status,
            'leaders' => $this->when($this->key === 'national-office', fn () => NationalLeaderResource::collection(
                NationalLeader::query()->where('is_published', true)->with('photo')->orderBy('display_order')->orderBy('name')->get(),
            )->resolve()),
        ];
    }
}
