<?php

namespace App\Http\Resources;

use App\Models\ChurchLeader;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ChurchLeader
 */
class ChurchLeaderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $admin = $request->is('api/v1/admin/*');

        return [
            'id' => $this->id,
            'church_id' => $this->when($admin, $this->church_id),
            'church' => $this->when($admin && $this->relationLoaded('church'), fn () => ['id' => $this->church?->id, 'name' => $this->church?->name]),
            'name' => $this->name,
            'position' => $this->position,
            'description' => $this->description,
            'photo_media_id' => $this->when($admin, $this->photo_media_id),
            'photo' => MediaResource::make($this->whenLoaded('photo')),
            'display_order' => $this->display_order,
            'is_published' => $this->when($admin, $this->is_published),
        ];
    }
}
