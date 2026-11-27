<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Church;
use App\Models\DailyMeditation;
use App\Models\DailyVerse;
use App\Models\DiscoverSection;
use App\Models\District;
use App\Models\Event;
use App\Models\News;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Collection;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Site public en lecture seule : il lit la base directement (pas l'API) et ne
 * pose aucun cookie. Contenu : national + district + église du site.
 */
class SiteController extends Controller
{
    /** Middlewares web retirés : le site ne pose aucun cookie. */
    public const STATELESS = [
        StartSession::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
        AddQueuedCookiesToResponse::class,
        EncryptCookies::class,
    ];

    private const RELATIONS = ['cover', 'district', 'church'];

    public function __construct()
    {
        view()->share('siteChurch', $this->church());
    }

    public function home(): View
    {
        $church = $this->church();

        return view('site.home', [
            'banners' => Banner::query()->visible()->with('media')->orderBy('display_order')->limit(5)->get(),
            'featured' => $this->newsQuery($church)->where('is_featured', true)->latest('published_at')->limit(3)->get(),
            'latest' => $this->newsQuery($church)->latest('published_at')->limit(6)->get(),
            'events' => $this->eventsQuery($church)->timeStatus('UPCOMING')->orderBy('start_at')->limit(4)->get(),
            'verse' => DailyVerse::query()->whereDate('publication_date', today())->first(),
            'meditation' => DailyMeditation::query()->published()->whereDate('publication_date', today())->first(),
            'social' => Setting::socialLinks(),
        ]);
    }

    private function newsQuery(?Church $church): mixed
    {
        return News::query()->published()->relevantTo($church)->with(self::RELATIONS);
    }

    public function newsIndex(Request $request): View
    {
        $scope = in_array($request->query('portee'), ['NATIONAL', 'DISTRICT', 'CHURCH'], true) ? $request->query('portee') : null;

        return view('site.news-index', [
            'items' => $this->newsQuery($this->church())
                ->when($scope, fn ($q) => $q->where('scope_type', $scope))
                ->orderByDesc('published_at')->paginate(12)->withQueryString(),
            'scope' => $scope,
        ]);
    }

    public function newsShow(string $slug): View
    {
        return view('site.news-show', [
            'item' => News::query()->published()->with(self::RELATIONS)->identifiedBy($slug)->firstOrFail(),
        ]);
    }

    private function eventsQuery(?Church $church): mixed
    {
        return Event::query()->published()->relevantTo($church)->with(self::RELATIONS);
    }

    public function eventsIndex(Request $request): View
    {
        $status = in_array($request->query('statut'), ['ONGOING', 'PAST'], true) ? $request->query('statut') : 'UPCOMING';

        return view('site.events-index', [
            'items' => $this->eventsQuery($this->church())->timeStatus($status)
                ->orderBy('start_at', $status === 'PAST' ? 'desc' : 'asc')->paginate(12)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function eventShow(string $slug): View
    {
        return view('site.event-show', [
            'item' => Event::query()->published()->with(self::RELATIONS)->identifiedBy($slug)->firstOrFail(),
        ]);
    }

    public function myChurch(): View
    {
        return $this->churchPage($this->church() ?? abort(404));
    }

    public function churches(Request $request): View
    {
        $district = $request->string('district')->toString();

        return view('site.churches-index', [
            'items' => Church::query()->active()->with(['district', 'image'])
                ->search($request->string('q')->toString())
                ->when($district !== '', fn ($q) => $q->where('district_id', $district))
                ->orderBy('name')->paginate(24)->withQueryString(),
            'districts' => District::query()->active()->orderBy('name')->get(['id', 'name']),
            'district' => $district,
            'q' => $request->string('q')->toString(),
        ]);
    }

    public function churchShow(string $slug): View
    {
        return $this->churchPage(Church::query()->active()->identifiedBy($slug)->firstOrFail());
    }

    public function meditations(): View
    {
        return view('site.meditations-index', [
            'items' => DailyMeditation::query()->published()->latest('publication_date')->paginate(12),
        ]);
    }

    public function meditationShow(string $id): View
    {
        return view('site.meditation-show', [
            'item' => DailyMeditation::query()->published()->with('image')->findOrFail($id),
        ]);
    }

    public function discover(): View
    {
        return view('site.discover-index', ['items' => $this->sections()->get()]);
    }

    public function discoverShow(string $key): View
    {
        return view('site.discover-show', [
            'item' => $this->sections()->where('key', $key)->firstOrFail(),
            'items' => $this->sections()->get(['key', 'title']),
        ]);
    }

    public function sitemap(): Response
    {
        $church = $this->church();
        $pages = ['/', '/actualites', '/evenements', '/eglises', '/meditations', '/decouvrir', '/privacy', '/legal'];
        $links = fn (Collection $slugs, string $route): array => $slugs->map(fn ($s): string => route($route, $s))->all();

        $urls = [
            ...array_map(fn (string $path): string => url($path), $church ? [...$pages, '/notre-eglise'] : $pages),
            ...$links($this->newsQuery($church)->latest('published_at')->limit(200)->pluck('slug'), 'site.news.show'),
            ...$links($this->eventsQuery($church)->latest('start_at')->limit(200)->pluck('slug'), 'site.events.show'),
            ...$links(Church::query()->active()->pluck('slug'), 'site.churches.show'),
            ...$links($this->sections()->pluck('key'), 'site.discover.show'),
        ];

        return response()->view('site.sitemap', ['urls' => $urls], 200, ['Content-Type' => 'application/xml']);
    }

    private function churchPage(Church $church): View
    {
        $church->load(['district', 'image']);

        return view('site.church-show', [
            'church' => $church,
            'news' => News::query()->published()->where('church_id', $church->id)->with('cover')->latest('published_at')->limit(3)->get(),
            'events' => Event::query()->published()->where('church_id', $church->id)->timeStatus('UPCOMING')->with('cover')->orderBy('start_at')->limit(3)->get(),
        ]);
    }

    private function sections(): mixed
    {
        return DiscoverSection::query()->where('status', 'PUBLISHED')->with('image')->orderBy('display_order');
    }

    /** Église du site, résolue une fois par requête. */
    private function church(): ?Church
    {
        return once(function (): ?Church {
            $key = config('site.church');

            return $key ? Church::query()->active()->identifiedBy((string) $key)->first() : null;
        });
    }
}
