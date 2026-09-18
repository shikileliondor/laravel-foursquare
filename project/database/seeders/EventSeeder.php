<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Media;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $folder = trim((string) config('media.folder', 'media'), '/').'/seed/';
        $convention = Media::query()->where('path', $folder.'convention.jpg')->firstOrFail();
        $convocation = Media::query()->where('path', $folder.'asok.jpg')->firstOrFail();
        $morasha = Media::query()->where('path', $folder.'morasha.jpg')->firstOrFail();
        $revelation = Zone::query()->where('slug', 'zone-revelation')->firstOrFail();

        // Les heures n'étant pas annoncées, les bornes de journée servent seulement au stockage SQL.
        Event::updateOrCreate(['slug' => 'convention-nationale-foursquare-ci-2026'], [
            'title' => 'Convention Nationale Foursquare CI 2026',
            'description' => 'Thème : La pluie en abondance. Référence : Zacharie 10:1.',
            'cover_media_id' => $convention->id,
            'scope_type' => 'NATIONAL',
            'organizer_name' => 'Église Évangélique Internationale Foursquare Côte d’Ivoire',
            'start_at' => '2026-08-12 00:00:00',
            'end_at' => '2026-08-16 23:59:59',
            'venue_name' => 'Riviera M’Badon',
            'address' => 'Près de l’Ambassade de Chine',
            'commune' => 'Cocody',
            'priority' => 'IMPORTANT',
            'status' => 'PUBLISHED',
        ]);

        Event::updateOrCreate(['slug' => 'convocation-panafricaine-2026'], [
            'title' => 'Convocation Panafricaine 2026',
            'description' => 'Organisée par le Département de la Jeunesse Foursquare Côte d’Ivoire. Participation Abidjan : 20 000 FCFA ; intérieur : 15 000 FCFA.',
            'cover_media_id' => $convocation->id,
            'scope_type' => 'NATIONAL',
            'organizer_name' => 'Département de la Jeunesse Foursquare Côte d’Ivoire',
            'start_at' => '2026-08-31 00:00:00',
            'end_at' => '2026-09-06 23:59:59',
            'venue_name' => 'ERA-SUD',
            'commune' => 'Bingerville',
            'priority' => 'IMPORTANT',
            'status' => 'PUBLISHED',
        ]);

        Event::updateOrCreate(['slug' => 'morasha-le-jet-du-manteau-2026'], [
            'title' => 'Morasha — Le Jet du Manteau',
            'description' => 'Rencontre organisée par les Églises Foursquare Ananeraie et Révélation.',
            'cover_media_id' => $morasha->id,
            'scope_type' => 'ZONE',
            'zone_id' => $revelation->id,
            'organizer_name' => 'Églises Foursquare Ananeraie et Révélation',
            'start_at' => '2026-09-10 00:00:00',
            'end_at' => '2026-09-12 23:59:59',
            'venue_name' => 'Église Foursquare Yopougon Ananeraie',
            'address' => 'Non loin du Carrefour Oasis',
            'commune' => 'Yopougon',
            'status' => 'PUBLISHED',
        ]);

        // Les affiches de la Semaine Spirituelle, des Offrandes et de l'Autel des Parfums
        // ne précisent pas l'année : aucun événement daté n'est créé pour elles.
    }
}
