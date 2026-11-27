<?php

namespace Database\Seeders;

use App\Models\Church;
use App\Models\District;
use App\Models\Event;
use App\Models\Media;
use App\Models\News;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Programmes réels tirés des affiches de public/assets/images. Sûr en
 * production :
 *
 *     php artisan db:seed --class=ProgrammesSeeder --force
 *
 * Idempotent : relancé, il ne crée aucun doublon. Un programme déjà présent
 * n'est enrichi que s'il n'a jamais été modifié depuis sa création (données
 * d'un ancien seeder) ; une correction faite dans le panel n'est jamais
 * écrasée. Seules les informations lisibles sur les affiches sont reprises.
 */
class ProgrammesSeeder extends Seeder
{
    /**
     * Affiche => chemin dans le stockage. Les anciennes affiches gardent le
     * chemin du seeder de démonstration pour réutiliser les médias existants.
     */
    private const IMAGES = [
        'a.jpg' => 'seed/a.jpg',
        'asok.jpg' => 'seed/asok.jpg',
        'athudj.jpg' => 'seed/athudj.jpg',
        'athur.jpg' => 'seed/athur.jpg',
        // Autel des Parfums du 20 septembre : remplacé par la « nouvelle date »
        // du 6 septembre (athur.jpg). Image gardée dans la médiathèque.
        'autel.jpg' => 'seed/autel.jpg',
        'convention.jpg' => 'seed/convention.jpg',
        'conventiona.jpg' => 'seed/conventiona.jpg',
        'morasha.jpg' => 'seed/morasha.jpg',
        'programme-salon-du-royaume.jpeg' => 'seed/programmes/programme-salon-du-royaume.jpeg',
        'programme-rendez-vous-revcom.jpeg' => 'seed/programmes/programme-rendez-vous-revcom.jpeg',
        'programme-peut-on-tout-demander-a-dieu.jpeg' => 'seed/programmes/programme-peut-on-tout-demander-a-dieu.jpeg',
        'programme-rentree-cotiere-2026.jpeg' => 'seed/programmes/programme-rentree-cotiere-2026.jpeg',
        'programme-autel-des-parfums-novembre-2026.jpeg' => 'seed/programmes/programme-autel-des-parfums-novembre-2026.jpeg',
        'programme-culte-louange-dimanche.jpeg' => 'seed/programmes/programme-culte-louange-dimanche.jpeg',
    ];

    private const NATIONAL_CHURCH = 'Église Évangélique Internationale Foursquare Côte d’Ivoire';

    private const NIANGON = 'Église Foursquare Niangon Révélation';

    public function run(): void
    {
        $this->call(LaunchSeeder::class);

        $cotiere = District::query()->where('slug', 'district-de-la-cotiere')->firstOrFail();
        $niangon = Church::query()->where('slug', 'eglise-foursquare-niangon-revelation')->firstOrFail();
        $media = $this->importImages();

        // Les heures non annoncées sur une affiche sont stockées sur la journée
        // entière (00:00 à 23:59:59) : l'application affiche alors la date seule.

        // --- Programmes nationaux -------------------------------------------
        $this->seedOnce(Event::class, 'convention-nationale-foursquare-ci-2026', [
            'title' => 'Convention Nationale Foursquare CI 2026',
            'description' => '<p>L’Église Évangélique Internationale Foursquare Côte d’Ivoire organise sa Convention Nationale, édition 2026.</p>'
                .'<p>Thème : « La pluie en abondance » — « Demandez à l’Éternel la pluie » (Zacharie 10:1).</p>'
                .'<p>Avec le Rév. Apôtre Gnepe K. Marius, président de l’Église Foursquare Côte d’Ivoire, et le Rév. Daouda Traoré, coordinateur FMI pour l’Afrique de l’Ouest et Centrale.</p>',
            'cover_media_id' => $media['convention.jpg'],
            'scope_type' => 'NATIONAL',
            'organizer_name' => self::NATIONAL_CHURCH,
            'start_at' => '2026-08-12 00:00:00',
            'end_at' => '2026-08-16 23:59:59',
            'venue_name' => 'Riviera M’Badon',
            'address' => 'Près de l’Ambassade de Chine',
            'commune' => 'Cocody',
            'priority' => 'IMPORTANT',
            'status' => 'PUBLISHED',
        ]);

        // L'ancien slug est conservé pour fusionner avec les données existantes.
        $this->seedOnce(Event::class, 'convocation-panafricaine-2026', [
            'title' => 'Convention Panafricaine 2026',
            'description' => '<p>Le Département de la Jeunesse de l’Église Foursquare Côte d’Ivoire organise la Convention Panafricaine 2026, en présentiel et en direct.</p>'
                .'<p>Thème : « Et j’eus encore un autre rêve » (Genèse 37:9).</p>'
                .'<p>Orateurs : Rév. Paul Simplice ; Rév. Gnepe Marius, président des Églises Foursquare Côte d’Ivoire ; Révérende Gnepe Deborah ; Rév. Antoine Marc Zando ; Prophète Gnahi Hervé, aumônier de la Jeunesse de l’Église Foursquare Côte d’Ivoire ; Rév. Aaron Hunter, président mondial de la Jeunesse des Églises Foursquare ; Prophète Ano Charles, président de la Jeunesse des Églises Foursquare Côte d’Ivoire ; Prophète Palenfo Raymond.</p>'
                .'<p>Participation : Abidjan 20 000 F ; intérieur 15 000 F.</p>',
            'cover_media_id' => $media['asok.jpg'],
            'scope_type' => 'NATIONAL',
            'organizer_name' => 'Département de la Jeunesse de l’Église Foursquare Côte d’Ivoire',
            'start_at' => '2026-08-31 00:00:00',
            'end_at' => '2026-09-06 23:59:59',
            'venue_name' => 'ERA-SUD',
            'commune' => 'Bingerville',
            'contact_phone' => '0707853699 / 0747291413',
            'priority' => 'IMPORTANT',
            'status' => 'PUBLISHED',
        ]);

        // Année absente de l'affiche : 2026, où le 13 septembre est bien le
        // dimanche annoncé.
        $this->seedOnce(Event::class, 'offrandes-orphelins-des-pasteurs-2026', [
            'title' => 'Offrandes des orphelins des pasteurs',
            'description' => '<p>Offrandes des orphelins des pasteurs, le dimanche 13 septembre, dans toutes les assemblées des 26 districts.</p>'
                .'<p>Infoline : 0708960691 / 0747690328.</p>',
            'cover_media_id' => $media['athudj.jpg'],
            'scope_type' => 'NATIONAL',
            'organizer_name' => self::NATIONAL_CHURCH,
            'start_at' => '2026-09-13 00:00:00',
            'end_at' => '2026-09-13 23:59:59',
            'venue_name' => 'Toutes les assemblées des 26 districts',
            'contact_phone' => '0708960691 / 0747690328',
            'status' => 'PUBLISHED',
        ]);

        // --- District de la Côtière -----------------------------------------
        $this->seedOnce(Event::class, 'morasha-le-jet-du-manteau-2026', [
            'title' => 'Morasha — Le Jet du Manteau',
            'description' => '<p>Les Églises Foursquare Ananeraie et Révélation organisent Morasha — Le Jet du Manteau (1 Rois 19:19).</p>'
                .'<p>Orateur principal : Rév. Prophète Roger Dindji. Avec le Rév. Emmanuel Dah, l’Évangéliste Solange Dah (hôte) et le Prophète Dieudonné Kouamé (invité).</p>',
            'cover_media_id' => $media['morasha.jpg'],
            'scope_type' => 'DISTRICT',
            'district_id' => $cotiere->id,
            'organizer_name' => 'Églises Foursquare Ananeraie et Révélation',
            'start_at' => '2026-09-10 00:00:00',
            'end_at' => '2026-09-12 23:59:59',
            'venue_name' => 'Église Foursquare Yopougon Ananeraie (Temple de la Grâce)',
            'address' => 'Non loin du Carrefour Oasis',
            'commune' => 'Yopougon',
            'contact_phone' => '0757014548',
            'status' => 'PUBLISHED',
        ]);

        $this->seedOnce(Event::class, 'rentree-ecclesiastique-cotiere-2026', [
            'title' => 'Rentrée ecclésiastique de la Côtière 2026',
            'description' => '<p>Le District de la Côtière présente sa rentrée ecclésiastique.</p>'
                .'<p>Thème : « Demandez à l’Éternel la pluie » (Zacharie 10:1).</p>'
                .'<p>Avec le Rév. Prophète Konan Kouassi Moïse, président du District de la Côtière.</p>',
            'cover_media_id' => $media['programme-rentree-cotiere-2026.jpeg'],
            'scope_type' => 'DISTRICT',
            'district_id' => $cotiere->id,
            'organizer_name' => 'District de la Côtière',
            'start_at' => '2026-10-17 08:00:00',
            'end_at' => '2026-10-17 13:00:00',
            'venue_name' => self::NIANGON,
            'address' => 'À 200 m du marché Bagnon',
            'commune' => 'Yopougon',
            'status' => 'PUBLISHED',
        ]);

        // --- Niangon Révélation ---------------------------------------------
        // Année absente de l'affiche (« nouvelle date ») : 2026, où le
        // 6 septembre est bien un dimanche.
        $this->seedOnce(Event::class, 'autel-des-parfums-septembre-2026', [
            'title' => 'Autel des Parfums — Septembre 2026',
            'description' => '<p>L’Église Évangélique Foursquare Niangon Révélation présente l’Autel des Parfums, à sa nouvelle date du dimanche 6 septembre.</p>'
                .'<p>Avec le Prophète Arthur Gnemagno.</p>',
            'cover_media_id' => $media['athur.jpg'],
            'scope_type' => 'CHURCH',
            'church_id' => $niangon->id,
            'organizer_name' => self::NIANGON,
            'start_at' => '2026-09-06 00:00:00',
            'end_at' => '2026-09-06 23:59:59',
            'venue_name' => self::NIANGON,
            'address' => 'Yopougon Maroc, non loin du marché Bagnon',
            'commune' => 'Yopougon',
            'status' => 'PUBLISHED',
        ]);

        $this->seedOnce(Event::class, 'autel-des-parfums-novembre-2026', [
            'title' => 'Autel des Parfums — Édition novembre 2026',
            'description' => '<p>L’Église Évangélique Foursquare Niangon Révélation présente l’Autel des Parfums, édition novembre 2026.</p>'
                .'<p>À suivre aussi sur YouTube : @foursquarerevelation.</p>',
            'cover_media_id' => $media['programme-autel-des-parfums-novembre-2026.jpeg'],
            'scope_type' => 'CHURCH',
            'church_id' => $niangon->id,
            'organizer_name' => self::NIANGON,
            'start_at' => '2026-11-29 00:00:00',
            'end_at' => '2026-11-29 23:59:59',
            'venue_name' => self::NIANGON,
            'address' => 'Yopougon Maroc, rue S22',
            'commune' => 'Yopougon',
            'contact_phone' => '0779177948 / 0502478435',
            'status' => 'PUBLISHED',
        ]);

        // Pas d'année sur l'affiche ni de jour de la semaine pour la vérifier :
        // annonce sans événement daté.
        $this->churchNews($niangon, 'niangon-revelation-semaine-spirituelle-jeunesse',
            'Semaine Spirituelle de la Jeunesse à Niangon Révélation',
            $media['a.jpg'],
            'Du 28 juillet au 2 août, sur le thème « Jeunesse entreprenante et investie dans la spiritualité ».',
            '<p>L’Église Évangélique Foursquare Niangon Révélation organise la Semaine Spirituelle de la Jeunesse, du 28 juillet au 2 août.</p>'
            .'<p>Thème : « Jeunesse entreprenante et investie dans la spiritualité ».</p>'
            .'<p>Lieu : Église Foursquare Niangon Révélation, Yopougon Maroc, non loin du marché Bagnon, rue S22. En direct sur YouTube et TikTok : @foursquareniangonrevelation.</p>',
        );

        foreach ([
            ['bientot-le-salon-du-royaume', 'Bientôt : Le Salon du Royaume', 'programme-salon-du-royaume.jpeg',
                'L’Église Évangélique Foursquare Niangon Révélation présente « Le Salon du Royaume ».'],
            ['bientot-le-rendez-vous-revcom', 'Bientôt : Le Rendez-vous RevCom', 'programme-rendez-vous-revcom.jpeg',
                'Un nouveau rendez-vous de RevCom, à l’Église Évangélique Foursquare Niangon Révélation.'],
            ['bientot-peut-on-tout-demander-a-dieu-episode-1', 'Bientôt : « Peut-on tout demander à Dieu ? » — Épisode 1', 'programme-peut-on-tout-demander-a-dieu.jpeg',
                'Premier épisode de « Peut-on tout demander à Dieu ? », par l’Église Évangélique Foursquare Niangon Révélation.'],
        ] as [$slug, $title, $image, $lead]) {
            $this->churchNews($niangon, $slug, $title, $media[$image],
                $lead.' Bientôt sur Foursquare Révélation.',
                '<p>'.$lead.'</p><p>Bientôt sur Foursquare Révélation.</p>'
                .'<p>Suivez @foursquarerevelation sur Instagram, Facebook et YouTube.</p>',
            );
        }

        $this->churchNews($niangon, 'culte-de-louange-et-d-adoration-dimanche',
            'Culte de louange et d’adoration, tous les dimanches',
            $media['programme-culte-louange-dimanche.jpeg'],
            'Tous les dimanches à partir de 08h00 à Niangon Révélation.',
            '<p>Culte de louange et d’adoration tous les dimanches, à partir de 08h00.</p>'
            .'<p>Venez vivre un moment spécial dans la présence de Dieu.</p>',
        );

        // Horaires de l'église : complétés seulement s'ils sont encore vides.
        $niangon->fill(array_filter([
            'main_service_day' => $niangon->main_service_day ? null : 'Dimanche',
            'main_service_time' => $niangon->main_service_time ? null : '08h00',
            'service_times' => $niangon->service_times ? null : 'Dimanche : culte de louange et d’adoration à partir de 08h00',
        ]))->save();

        $this->command->info('Programmes : '.count(self::IMAGES).' affiches, 7 événements, 5 actualités.');
        $this->command->warn('Année 2026 déduite (absente des affiches, jour de la semaine cohérent) : Autel des Parfums du 6 septembre, Offrandes des orphelins du 13 septembre. Semaine Spirituelle de la Jeunesse publiée en actualité, sans année.');
    }

    /**
     * Copie les affiches dans le stockage des médias.
     *
     * @return array<string, string> nom du fichier => id du média
     */
    private function importImages(): array
    {
        $disk = Storage::disk((string) config('media.disk', 'public'));
        $folder = trim((string) config('media.folder', 'media'), '/');
        $ids = [];

        foreach (self::IMAGES as $filename => $relative) {
            $source = public_path('assets/images/'.$filename);
            $bytes = is_file($source) ? file_get_contents($source) : false;
            $image = $bytes !== false ? getimagesize($source) : false;

            if ($bytes === false || $image === false) {
                throw new RuntimeException("Affiche introuvable ou invalide : public/assets/images/{$filename}");
            }

            $path = $folder.'/'.$relative;

            if (! $disk->exists($path) && ! $disk->put($path, $bytes)) {
                throw new RuntimeException("Impossible d'enregistrer l'affiche : {$filename}");
            }

            $ids[$filename] = Media::query()->firstOrCreate(['path' => $path], [
                'original_name' => $filename,
                'stored_name' => $filename,
                'mime_type' => $image['mime'],
                'file_size' => strlen($bytes),
                'width' => $image[0],
                'height' => $image[1],
            ])->id;
        }

        return $ids;
    }

    /**
     * Crée le programme, ou l'enrichit s'il n'a jamais été modifié depuis sa
     * création. Un programme corrigé dans le panel reste tel quel.
     *
     * @param  class-string<Event|News>  $model
     * @param  array<string, mixed>  $attributes
     */
    private function seedOnce(string $model, string $slug, array $attributes): void
    {
        $existing = $model::query()->where('slug', $slug)->first();

        if (! $existing) {
            $model::query()->create(['slug' => $slug] + $attributes);

            return;
        }

        $untouched = $existing->created_at && $existing->updated_at
            && $existing->updated_at->equalTo($existing->created_at);

        if ($untouched) {
            $existing->update($attributes);
        }
    }

    private function churchNews(Church $church, string $slug, string $title, string $coverId, string $excerpt, string $content): void
    {
        $this->seedOnce(News::class, $slug, [
            'title' => $title,
            'excerpt' => $excerpt,
            'content' => $content,
            'cover_media_id' => $coverId,
            'scope_type' => 'CHURCH',
            'church_id' => $church->id,
            'district_id' => null,
            'priority' => 'NORMAL',
            'status' => 'PUBLISHED',
        ]);
    }
}
