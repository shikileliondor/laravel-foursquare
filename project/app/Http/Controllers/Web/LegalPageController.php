<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Pages publiques exigées par Google Play et l'App Store : politique de
 * confidentialité et mentions légales, en HTML, sans connexion.
 */
class LegalPageController extends Controller
{
    private const PAGES = [
        'privacy' => 'Politique de confidentialité',
        'legal' => 'Mentions légales et conditions d’utilisation',
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
