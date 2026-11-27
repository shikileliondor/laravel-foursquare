<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ $title }} — Foursquare CI</title>
        <style>
            body { margin: 0; background: #f4f6fb; color: #0e1630; font: 16px/1.65 system-ui, -apple-system, 'Segoe UI', sans-serif; }
            header { background: #0e1a3d; color: #fff; padding: 28px 20px; }
            header div, main { max-width: 820px; margin: 0 auto; }
            header strong { font-size: 20px; }
            header p { margin: 4px 0 0; color: #c3cbe4; }
            .bar { display: flex; height: 4px; }
            .bar i { flex: 1; } .bar i:nth-child(1) { background: #e31e2d; } .bar i:nth-child(2) { background: #2449b8; } .bar i:nth-child(3) { background: #f2b705; } .bar i:nth-child(4) { background: #7e2a8c; }
            main { padding: 28px 20px 60px; }
            article { background: #fff; border: 1px solid #e4e8f0; border-radius: 14px; padding: 8px 28px 28px; }
            h1, h2, h3 { line-height: 1.25; } h2 { margin-top: 32px; } a { color: #2449b8; }
            table { width: 100%; border-collapse: collapse; font-size: 14px; display: block; overflow-x: auto; }
            th, td { border: 1px solid #e4e8f0; padding: 8px 10px; text-align: left; vertical-align: top; }
            nav { display: flex; flex-wrap: wrap; gap: 8px 18px; margin-top: 18px; font-size: 14px; }
            @media (max-width: 560px) { article { padding: 4px 18px 22px; } }
        </style>
    </head>
    <body>
        <header><div><strong>Foursquare CI</strong><p>{{ $title }}</p></div></header>
        <div class="bar"><i></i><i></i><i></i><i></i></div>
        <main>
            <article>{!! $html !!}</article>
            <nav aria-label="Pages juridiques">
                <a href="{{ url('/privacy') }}">Confidentialité</a>
                <a href="{{ url('/cookies') }}">Cookies et stockage local</a>
                <a href="{{ url('/consent') }}">Consentement</a>
                <a href="{{ url('/legal') }}">Mentions légales</a>
                <a href="{{ url('/terms') }}">Conditions d’utilisation</a>
            </nav>
        </main>
    </body>
</html>
