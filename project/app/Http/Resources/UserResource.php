<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Support\Perimeter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'role_label' => $this->adminRole()?->label(),
            'status' => $this->status,
            'district_id' => $this->district_id,
            'church_id' => $this->church_id,
            'district' => $this->whenLoaded('district', fn () => $this->district ? ['id' => $this->district->id, 'name' => $this->district->name] : null),
            'church' => $this->whenLoaded('church', fn () => $this->church ? ['id' => $this->church->id, 'name' => $this->church->name] : null),
            // Modules du panel ouverts à ce compte ; le serveur vérifie de toute façon chaque action.
            'permissions' => Perimeter::modules($this->resource),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
        ];
    }
}
