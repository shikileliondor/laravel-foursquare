<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\BannerResource;
use App\Http\Resources\ChurchResource;
use App\Http\Resources\DailyVerseResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\NewsResource;
use App\Models\Banner;
use App\Models\Church;
use App\Models\DailyVerse;
use App\Models\Event;
use App\Models\News;
use App\Models\Setting;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends ApiController
{
    /**
     * Single payload for the Flutter home screen.
     *
     * Avec `for_church_id` : le national, le district et l'église choisie.
     * Sans église : le national seul.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $church = $this->referenceChurch($request);

        $banners = Banner::query()->visible()->with('media')->orderBy('display_order')->limit(10)->get();

        $news = fn () => News::query()->published()->relevantTo($church)->with(self::PUBLIC_CONTENT_RELATIONS);
        $events = fn () => Event::query()->published()->relevantTo($church)->with(self::PUBLIC_CONTENT_RELATIONS);

        $featuredNews = $news()->where('is_featured', true)
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $featuredEvent = $events()->where('is_featured', true)->timeStatus('UPCOMING')->orderBy('start_at')->first();
        $latestNews = $news()->latest('published_at')->limit(10)->get();
        $upcomingEvents = $events()->timeStatus('UPCOMING')->orderBy('start_at')->limit(10)->get();

        return ApiResponse::ok([
            'daily_verse' => ($verse = DailyVerse::query()->whereDate('publication_date', today())->first())
                ? DailyVerseResource::make($verse)->resolve()
                : null,
            'church' => $church ? ChurchResource::make($church->load(['district', 'image']))->resolve() : null,
            'launch_church' => $this->launchChurch(),
            'banners' => BannerResource::collection($banners)->resolve(),
            'featured_news' => NewsResource::collection($featuredNews)->resolve(),
            'featured_news_item' => $featuredNews->first() ? NewsResource::make($featuredNews->first())->resolve() : null,
            'featured_event' => $featuredEvent ? EventResource::make($featuredEvent)->resolve() : null,
            'latest_news' => NewsResource::collection($latestNews)->resolve(),
            'upcoming_events' => EventResource::collection($upcomingEvents)->resolve(),
        ]);
    }

    /**
     * Église proposée par défaut au premier lancement, réglée dans les
     * paramètres du back-office (jamais codée en dur).
     *
     * @return array<string, mixed>|null
     */
    private function launchChurch(): ?array
    {
        $id = Setting::read('launch_church_id');
        $church = $id ? Church::query()->active()->with(['district', 'image'])->find($id) : null;

        return $church ? ChurchResource::make($church)->resolve() : null;
    }
}
