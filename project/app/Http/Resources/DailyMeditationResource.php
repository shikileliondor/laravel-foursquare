<?php

namespace App\Http\Resources;

use App\Models\DailyMeditation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Les listes et l'accueil n'envoient que l'extrait : le texte complet part
 * avec le détail, la méditation du jour et l'administration.
 *
 * @mixin DailyMeditation
 */
class DailyMeditationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $admin = $request->routeIs('v1.admin.*');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'scripture_reference' => $this->scripture_reference,
            'publication_date' => $this->publication_date->toDateString(),
            'image_media_id' => $this->image_media_id,
            'image' => MediaResource::make($this->whenLoaded('image')),
            'excerpt' => $this->excerpt(),
            'content' => $this->when(
                $admin || $request->routeIs('v1.meditations.show', 'v1.meditations.today'),
                $this->content,
            ),
            'notification_created_at' => $this->when($admin, $this->notification_created_at?->toIso8601String()),
            'notification' => $this->when(
                $admin && $this->relationLoaded('notification'),
                fn () => $this->notification?->summary(),
            ),
        ];
    }
}
