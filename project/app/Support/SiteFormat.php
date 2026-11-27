<?php

namespace App\Support;

use App\Models\Banner;
use App\Models\Church;
use App\Models\Event;
use App\Models\News;
use App\Services\HtmlSanitizer;
use Carbon\CarbonInterface;

/** Mise en forme française du site public (dates, portées, liens). */
final class SiteFormat
{
    public static function date(?CarbonInterface $date): string
    {
        return $date ? $date->copy()->timezone('Africa/Abidjan')->locale('fr')->translatedFormat('l j F Y') : '';
    }

    public static function dateTime(?CarbonInterface $date): string
    {
        if (! $date) {
            return '';
        }

        $local = $date->copy()->timezone('Africa/Abidjan')->locale('fr');

        return $local->translatedFormat('l j F Y').' à '.$local->format('G').' h '.$local->format('i');
    }

    /** « dimanche 7 décembre 2026 à 9 h 00 – 12 h 00 », ou deux dates. */
    public static function eventWhen(Event $event): string
    {
        $start = self::dateTime($event->start_at);
        $end = $event->end_at->copy()->timezone('Africa/Abidjan');

        return $event->start_at->copy()->timezone('Africa/Abidjan')->isSameDay($end)
            ? $start.' – '.$end->format('G').' h '.$end->format('i')
            : $start.' au '.self::dateTime($event->end_at);
    }

    /**
     * @return array{label: string, tone: string}
     */
    public static function scope(News|Event $item): array
    {
        return [
            'label' => Audience::sourceLabel($item->scope_type, $item->district?->name, $item->church?->name),
            'tone' => match ($item->scope_type) {
                'DISTRICT' => 'district',
                'CHURCH' => 'church',
                default => 'national',
            },
        ];
    }

    public static function html(?string $html): string
    {
        return HtmlSanitizer::clean((string) $html);
    }

    public static function mapUrl(Event|Church $place): ?string
    {
        if ($place->latitude && $place->longitude) {
            return 'https://www.google.com/maps/search/?api=1&query='.$place->latitude.','.$place->longitude;
        }

        $address = collect([$place->address, $place->commune ?? null])->filter()->implode(', ');

        return $address === '' ? null : 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($address.', Côte d’Ivoire');
    }

    public static function whatsapp(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '' || $digits === null) {
            return null;
        }

        // Numéro ivoirien local à 10 chiffres : indicatif 225.
        return 'https://wa.me/'.(strlen($digits) === 10 ? '225'.$digits : $digits);
    }

    /** Lien d'une bannière vers sa cible, ou null si elle n'en a pas. */
    public static function bannerUrl(Banner $banner): ?string
    {
        $slug = fn (string $model, ?string $id): ?string => $id ? $model::query()->whereKey($id)->value('slug') : null;

        return match ($banner->link_type) {
            'NEWS' => ($s = $slug(News::class, $banner->news_id)) ? route('site.news.show', $s) : null,
            'EVENT' => ($s = $slug(Event::class, $banner->event_id)) ? route('site.events.show', $s) : null,
            'CHURCH' => ($s = $slug(Church::class, $banner->church_id)) ? route('site.churches.show', $s) : null,
            'EXTERNAL' => $banner->external_url ?: null,
            default => null,
        };
    }

    public static function tel(?string $phone): ?string
    {
        $first = trim(explode('/', (string) $phone)[0]);

        return $first === '' ? null : 'tel:'.preg_replace('/[^\d+]/', '', $first);
    }
}
