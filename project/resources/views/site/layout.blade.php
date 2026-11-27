@php($siteName = config('site.name'))
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trim($__env->yieldContent('title')) ? trim($__env->yieldContent('title')).' · '.$siteName : $siteName }}</title>
    <meta name="description" content="@yield('description', 'Actualités, événements, méditations et églises de la famille Foursquare en Côte d’Ivoire.')">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="@yield('title', $siteName)">
    <meta property="og:description" content="@yield('description', 'Actualités, événements et méditations Foursquare.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')<meta property="og:image" content="@yield('og_image')">@endif
    <meta name="theme-color" content="#0E1A3D">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="/admin/logo.jpg">
    <link rel="stylesheet" href="/site/site.css?v=1">
    @stack('head')
</head>
<body>
<a class="skip" href="#contenu">Aller au contenu</a>
<header class="top">
    <div class="wrap top-row">
        <a class="brand" href="{{ route('home') }}">
            <img src="/admin/logo.jpg" alt="" width="40" height="40">
            <span>{{ $siteName }}</span>
        </a>
    </div>
    <nav class="wrap nav" aria-label="Navigation principale">
        @php($nav = array_filter([
            ['home', 'Accueil', 'home'],
            ['site.news.index', 'Actualités', 'site.news.*'],
            ['site.events.index', 'Événements', 'site.events.*'],
            ($siteChurch ?? null) ? ['site.church', 'Notre église', 'site.church'] : null,
            ['site.churches.index', 'Églises', 'site.churches.*'],
            ['site.meditations.index', 'Méditations', 'site.meditations.*'],
            ['site.discover.index', 'Découvrir', 'site.discover.*'],
        ]))
        @foreach ($nav as [$route, $label, $pattern])
            <a href="{{ route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
</header>
<main id="contenu" class="wrap">
    @yield('content')
</main>
<footer class="foot">
    <div class="wrap foot-grid">
        <div>
            <strong>{{ $siteName }}</strong>
            @if ($siteChurch ?? null)
                <p>{{ $siteChurch->district?->name }}</p>
            @endif
            <p>Église Évangélique Internationale Foursquare Côte d’Ivoire</p>
        </div>
        <nav aria-label="Informations">
            <a href="/privacy">Confidentialité</a>
            <a href="/legal">Mentions légales</a>
            <a href="/terms">Conditions d’utilisation</a>
        </nav>
    </div>
</footer>
</body>
</html>
