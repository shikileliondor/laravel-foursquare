<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\BannerResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\NewsResource;
use App\Models\Banner;
use App\Models\Event;
use App\Models\News;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class HomeController extends ApiController
{
    /**
     * Single payload for the Flutter home screen.
     */
    public function __invoke(): JsonResponse
    {
        $banners = Banner::query()->visible()->with('media')->orderBy('display_order')->limit(10)->get();

        $featuredNews = News::query()->published()->with('cover')
            ->where('is_featured', true)->latest('published_at')->first();

        $featuredEvent = Event::query()->published()->with('cover')
            ->where('is_featured', true)->timeStatus('UPCOMING')->orderBy('start_at')->first();

        $latestNews = News::query()->published()->with('cover')
            ->latest('published_at')->limit(10)->get();

        $upcomingEvents = Event::query()->published()->with('cover')
            ->timeStatus('UPCOMING')->orderBy('start_at')->limit(10)->get();

        return ApiResponse::ok([
            'banners' => BannerResource::collection($banners)->resolve(),
            'featured_news' => $featuredNews ? NewsResource::make($featuredNews)->resolve() : null,
            'featured_event' => $featuredEvent ? EventResource::make($featuredEvent)->resolve() : null,
            'latest_news' => NewsResource::collection($latestNews)->resolve(),
            'upcoming_events' => EventResource::collection($upcomingEvents)->resolve(),
        ]);
    }
}
