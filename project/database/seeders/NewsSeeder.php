<?php

namespace Database\Seeders;

use App\Models\Church;
use App\Models\Media;
use App\Models\News;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $folder = trim((string) config('media.folder', 'media'), '/').'/seed/';
        $convention = Media::query()->where('path', $folder.'conventiona.jpg')->firstOrFail();
        $convocation = Media::query()->where('path', $folder.'asok.jpg')->firstOrFail();
        $youth = Media::query()->where('path', $folder.'a.jpg')->firstOrFail();
        $morasha = Media::query()->where('path', $folder.'morasha.jpg')->firstOrFail();
        $niangon = Church::query()->where('slug', 'eglise-foursquare-niangon-revelation')->firstOrFail();
        $revelation = Zone::query()->where('slug', 'zone-revelation')->firstOrFail();

        News::updateOrCreate(['slug' => 'convention-nationale-2026-annoncee'], [
            'title' => 'La Convention Nationale 2026 annoncée',
            'excerpt' => 'La Convention Nationale Foursquare CI s’est tenue du 12 au 16 août 2026.',
            'content' => 'Rendez-vous à Cocody, Riviera M’Badon, près de l’Ambassade de Chine. Thème : La pluie en abondance (Zacharie 10:1).',
            'cover_media_id' => $convention->id,
            'scope_type' => 'NATIONAL',
            'priority' => 'IMPORTANT',
            'is_featured' => true,
            'status' => 'PUBLISHED',
        ]);

        News::updateOrCreate(['slug' => 'convocation-panafricaine-jeunesse-2026'], [
            'title' => 'La Convocation Panafricaine de la Jeunesse approche',
            'excerpt' => 'La Convocation Panafricaine 2026 est annoncée du 31 août au 6 septembre.',
            'content' => 'Le Département de la Jeunesse Foursquare Côte d’Ivoire donne rendez-vous à ERA-SUD, Bingerville.',
            'cover_media_id' => $convocation->id,
            'scope_type' => 'NATIONAL',
            'priority' => 'IMPORTANT',
            'is_featured' => true,
            'status' => 'PUBLISHED',
        ]);

        News::updateOrCreate(['slug' => 'niangon-revelation-semaine-spirituelle-jeunesse'], [
            'title' => 'Niangon Révélation organise sa Semaine Spirituelle de la Jeunesse',
            'excerpt' => 'Du 28 juillet au 2 août ; l’année n’est pas précisée sur l’affiche.',
            'content' => 'Thème : Jeunesse entreprenante et investie dans la spiritualité. Église Foursquare Niangon Révélation, secteur Maroc, non loin du marché Bagnon, rue S22.',
            'cover_media_id' => $youth->id,
            'scope_type' => 'CHURCH',
            'church_id' => $niangon->id,
            'priority' => 'NORMAL',
            'is_featured' => true,
            'status' => 'PUBLISHED',
        ]);

        News::updateOrCreate(['slug' => 'ananeraie-revelation-morasha-2026'], [
            'title' => 'Les Églises Ananeraie et Révélation organisent Morasha',
            'excerpt' => 'Morasha — Le Jet du Manteau, du 10 au 12 septembre 2026.',
            'content' => 'Rencontre organisée à l’Église Foursquare Yopougon Ananeraie, non loin du Carrefour Oasis.',
            'cover_media_id' => $morasha->id,
            'scope_type' => 'ZONE',
            'zone_id' => $revelation->id,
            'priority' => 'NORMAL',
            'is_featured' => true,
            'status' => 'PUBLISHED',
        ]);
    }
}
