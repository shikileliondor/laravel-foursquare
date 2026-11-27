<?php

namespace Database\Seeders;

use App\Models\DiscoverSection;
use App\Models\NationalLeader;
use App\Services\HtmlSanitizer;
use Illuminate\Database\Seeder;

/**
 * Contenus de « Découvrir Foursquare » rassemblés par REVCOM à partir de
 * sources publiques (Fraternité Matin, AIP, iChretien, Foursquare
 * International). Tout reste en BROUILLON : la direction nationale valide puis
 * publie depuis le panel.
 *
 *     php artisan db:seed --class=DiscoverSeeder --force
 *
 * Ne remplit qu'une rubrique vide et ne crée un responsable que s'il n'existe
 * pas : une saisie faite dans le panel n'est jamais écrasée.
 */
class DiscoverSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->sections() as $key => $html) {
            $section = DiscoverSection::query()->where('key', $key)->first();

            if ($section && blank($section->content)) {
                $section->update(['content' => HtmlSanitizer::clean($html)]);
            }
        }

        foreach ([
            ['Révérend Apôtre Dr Kouamé Gnépé Marius', 'Président national'],
            ['Révérend Dr Gbelia Roger', 'Directeur de cabinet du président national'],
            ['Prophète Ba Israël', 'Secrétaire général'],
        ] as $order => [$name, $position]) {
            NationalLeader::query()->firstOrCreate(['name' => $name], [
                'position' => $position,
                'display_order' => $order + 1,
                'is_published' => false,
            ]);
        }

        $this->command->info('Découvrir Foursquare : rubriques remplies en brouillon, 3 responsables non publiés.');
        $this->command->warn('À valider par la direction nationale avant publication. Bureau national incomplet ; photos à ajouter.');
    }

    /**
     * @return array<string, string>
     */
    private function sections(): array
    {
        return [
            'history' => '<h2>Les origines de Foursquare</h2>'
                .'<p>L’Église Internationale Foursquare trouve ses origines aux États-Unis, sous l’impulsion d’Aimee Semple McPherson, évangéliste et missionnaire.</p>'
                .'<p>En 1923, elle inaugure l’Angelus Temple à Los Angeles, en Californie. Le mouvement se développe autour de la proclamation de Jésus-Christ à travers les quatre dimensions fondamentales de l’Évangile Foursquare.</p>'
                .'<p>En 1927, le mouvement entreprend sa constitution officielle sous le nom d’International Church of the Foursquare Gospel. Il se développe ensuite à travers le monde grâce à l’évangélisation, à la formation des disciples et à l’implantation d’églises.</p>'
                .'<h2>L’implantation en Côte d’Ivoire</h2>'
                .'<p>Les premiers contacts ayant conduit à l’implantation de Foursquare en Côte d’Ivoire remontent à 1986, avec la rencontre du pasteur ivoirien Kouassi Philippe et du missionnaire canadien Michel Belobaba.</p>'
                .'<p>En 1989, les activités de la mission démarrent effectivement à Yopougon, dans le quartier SIDECI Kotibet. Le Révérend Kouassi Philippe devient le premier président national.</p>'
                .'<p>Sous la direction successive de plusieurs serviteurs de Dieu, notamment le prophète Kacou Sévérin, l’Église connaît une expansion importante à travers l’implantation de nouvelles assemblées.</p>'
                .'<p>Depuis 2001, le Révérend Apôtre Docteur Kouamé Gnépé Marius assure la présidence nationale et poursuit le développement de l’œuvre missionnaire.</p>'
                .'<h2>Quelques dates importantes</h2>'
                .'<ul>'
                .'<li><strong>1923</strong> : inauguration de l’Angelus Temple aux États-Unis</li>'
                .'<li><strong>1927</strong> : constitution de l’organisation internationale</li>'
                .'<li><strong>1986</strong> : premiers contacts missionnaires en Côte d’Ivoire</li>'
                .'<li><strong>1989</strong> : début effectif des activités en Côte d’Ivoire</li>'
                .'<li><strong>2001</strong> : début de la présidence du Révérend Kouamé Gnépé Marius</li>'
                .'<li><strong>2021</strong> : entrée de la Côte d’Ivoire au Bureau exécutif du Conseil mondial de Foursquare</li>'
                .'<li><strong>2026</strong> : organisation de la 36ᵉ Convention nationale</li>'
                .'</ul>',

            'vision-missions' => '<h2>Notre vision</h2>'
                .'<p>Annoncer l’Évangile de Jésus-Christ à toutes les nations, former des disciples et développer des communautés chrétiennes animées par la puissance du Saint-Esprit.</p>'
                .'<h2>Nos missions</h2>'
                .'<ul>'
                .'<li>Proclamer le salut en Jésus-Christ.</li>'
                .'<li>Évangéliser les peuples et les nations.</li>'
                .'<li>Former des disciples et des responsables spirituels.</li>'
                .'<li>Implanter et développer des églises locales.</li>'
                .'<li>Encourager la croissance spirituelle des croyants.</li>'
                .'<li>Servir les populations et contribuer au bien-être des communautés.</li>'
                .'<li>Promouvoir l’unité et la communion fraternelle.</li>'
                .'</ul>'
                .'<p>Fondement biblique : Matthieu 28:19-20.</p>'
                .'<blockquote>« Je suis Foursquare, je suis missionnaire. »</blockquote>',

            'doctrine' => '<p>L’Église Foursquare est un mouvement chrétien évangélique de tradition pentecôtiste, fondé sur l’autorité de la Bible et centré sur la personne et l’œuvre de Jésus-Christ. Son identité repose sur quatre proclamations fondamentales.</p>'
                .'<h3>Jésus-Christ, le Sauveur</h3>'
                .'<p>Jésus-Christ offre le pardon des péchés et la réconciliation avec Dieu par son sacrifice à la croix et sa résurrection.</p><p><em>Ésaïe 53:5 · Jean 3:16</em></p>'
                .'<h3>Jésus-Christ, le Baptiseur du Saint-Esprit</h3>'
                .'<p>Jésus-Christ accorde le baptême du Saint-Esprit afin d’équiper les croyants pour le témoignage, le service et l’accomplissement de la mission chrétienne.</p><p><em>Actes 1:5-8 · Actes 2:4</em></p>'
                .'<h3>Jésus-Christ, le Guérisseur</h3>'
                .'<p>L’Église croit à la guérison divine et encourage la prière avec foi en faveur des personnes malades et souffrantes.</p><p><em>Matthieu 8:16-17 · Jacques 5:14-15</em></p>'
                .'<h3>Jésus-Christ, le Roi qui revient</h3>'
                .'<p>L’Église croit au retour de Jésus-Christ et appelle les fidèles à vivre dans la foi, la fidélité et l’espérance de son avènement.</p><p><em>1 Thessaloniciens 4:16-17 · Tite 2:13</em></p>'
                .'<h2>Notre fondement biblique</h2>'
                .'<blockquote>« Jésus-Christ est le même hier, aujourd’hui, et éternellement. » — Hébreux 13:8</blockquote>'
                .'<p>La déclaration de foi internationale comprend également 22 articles doctrinaux portant notamment sur les Saintes Écritures, la Trinité, le salut, la nouvelle naissance, le baptême, la Sainte-Cène, la vie chrétienne et l’éternité.</p>',

            'national-office' => '<p>Le bureau national accompagne le président national dans la direction de l’Église Évangélique Internationale Foursquare Côte d’Ivoire.</p>',

            'organization' => '<p>L’Église Évangélique Internationale Foursquare Côte d’Ivoire appartient à la grande famille internationale Foursquare. Son organisation s’appuie notamment sur une direction nationale, des districts et des assemblées locales.</p>'
                .'<h3>Le niveau national</h3>'
                .'<p>La direction nationale assure l’orientation générale du ministère, la coordination des activités nationales et le développement de l’œuvre missionnaire en Côte d’Ivoire.</p>'
                .'<h3>Les districts</h3>'
                .'<p>Les districts regroupent des assemblées locales et assurent leur coordination territoriale ainsi que l’organisation d’activités communes.</p>'
                .'<h3>Les églises locales</h3>'
                .'<p>Les églises locales constituent les communautés de proximité dans lesquelles les fidèles se réunissent pour les cultes, la prière, les enseignements bibliques et les activités chrétiennes.</p>'
                .'<h3>Le siège national</h3>'
                .'<p>Cocody, Riviera M’Badon, Abidjan, Côte d’Ivoire. Le siège accueille notamment les conventions nationales du ministère.</p>',

            'districts-churches' => '<p>L’Église Foursquare Côte d’Ivoire dispose d’un réseau d’assemblées réparties sur le territoire national.</p>'
                .'<h3>Chiffres publiés en août 2025</h3>'
                .'<ul>'
                .'<li><strong>33 300</strong> fidèles</li>'
                .'<li><strong>701</strong> églises</li>'
                .'<li><strong>560</strong> pasteurs</li>'
                .'<li><strong>31</strong> régions couvertes</li>'
                .'</ul>'
                .'<p><em>Source : 35ᵉ Convention nationale, août 2025.</em></p>'
                .'<p>Retrouvez toutes les églises et leurs coordonnées dans l’annuaire.</p>',
        ];
    }
}
