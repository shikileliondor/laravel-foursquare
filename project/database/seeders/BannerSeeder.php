<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Event;
use App\Models\Media;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $folder = trim((string) config('media.folder', 'media'), '/').'/seed/';

        $banners = [
            ['Convention Nationale 2026', 'convention.jpg', 'convention-nationale-foursquare-ci-2026'],
            ['Convocation Panafricaine 2026', 'asok.jpg', 'convocation-panafricaine-2026'],
            ['Morasha — Le Jet du Manteau', 'morasha.jpg', 'morasha-le-jet-du-manteau-2026'],
        ];

        foreach ($banners as $index => [$title, $filename, $eventSlug]) {
            $media = Media::query()->where('path', $folder.$filename)->firstOrFail();
            $event = Event::query()->where('slug', $eventSlug)->firstOrFail();

            Banner::updateOrCreate(['title' => $title], [
                'media_id' => $media->id,
                'link_type' => 'EVENT',
                'event_id' => $event->id,
                'display_order' => $index + 1,
                'is_active' => true,
                // Les affiches restent en base, mais ne s'affichent plus après l'événement.
                'ends_at' => $event->end_at,
            ]);
        }
    }
}
