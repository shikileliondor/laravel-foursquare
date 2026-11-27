<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Pages juridiques publiques, rendues en HTML et accessibles sans connexion.
 */
class LegalPageController extends Controller
{
    private const PAGES = [
        'privacy' => 'Politique de confidentialité',
        'legal' => 'Mentions légales',
        'terms' => 'Conditions d’utilisation',
        'cookies' => 'Cookies et stockage local',
        'consent' => 'Politique de consentement',
    ];

    public function __invoke(string $page): Response
    {
        abort_unless(isset(self::PAGES[$page]), 404);

        $markdown = (string) file_get_contents(resource_path("legal/{$page}.md"));

        return response()
            ->view('legal', [
                'title' => self::PAGES[$page],
                'html' => Str::markdown($markdown, ['html_input' => 'escape', 'allow_unsafe_links' => false]),
            ])
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
