<?php

namespace App\Http\Resources;

use App\Models\NationalLeader;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin NationalLeader
 */
class NationalLeaderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'photo_media_id' => $this->photo_media_id,
            'photo' => MediaResource::make($this->whenLoaded('photo')),
            'display_order' => $this->display_order,
            'is_published' => $this->is_published,
        ];
    }
}
