<?php

namespace App\Http\Resources;

use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Zone
 */
class ZoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'district_id' => $this->district_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'code' => $this->code,
            'description' => $this->description,
            'status' => $this->status,
            'district' => DistrictResource::make($this->whenLoaded('district')),
            'churches_count' => $this->whenCounted('churches'),
        ];
    }
}
