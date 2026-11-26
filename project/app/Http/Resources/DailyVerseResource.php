<?php

namespace App\Http\Resources;

use App\Models\DailyVerse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DailyVerse
 */
class DailyVerseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'reference' => $this->reference,
            'bible_version' => $this->bible_version,
            'publication_date' => $this->publication_date->toDateString(),
            'notification_created_at' => $this->when(
                $request->routeIs('v1.admin.*'),
                $this->notification_created_at?->toIso8601String(),
            ),
        ];
    }
}
