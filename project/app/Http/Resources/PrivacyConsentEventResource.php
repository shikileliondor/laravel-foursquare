<?php

namespace App\Http\Resources;

use App\Models\PrivacyConsentEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PrivacyConsentEvent
 */
class PrivacyConsentEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject_id' => $this->subject_id,
            'purpose' => $this->purpose,
            'action' => $this->action,
            'policy_version' => $this->policy_version,
            'platform' => $this->platform,
            'church_id' => $this->church_id,
            'church' => $this->whenLoaded('church', fn () => $this->church ? ['id' => $this->church->id, 'name' => $this->church->name] : null),
            'event_id' => $this->event_id,
            'occurred_at' => $this->occurred_at->toIso8601String(),
        ];
    }
}
