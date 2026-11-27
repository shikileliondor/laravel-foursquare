# Foursquare Côte d'Ivoire — Backend et panel d'administration

**Une application nationale, une expérience locale.**

Foursquare CI est l'application mobile de l'Église Évangélique Internationale Foursquare Côte
d'Ivoire. Elle sert à diffuser les informations, organiser les événements et accompagner la vie
spirituelle des fidèles. Les fidèles et les visiteurs l'utilisent **sans créer de compte**.

Ce dépôt contient l'API REST (Laravel) et le panel d'administration web. L'application Flutter
est dans un dépôt séparé.

## Organisation

La plateforme suit la structure de l'Église : **National → Districts → Églises locales**, puis
les fidèles et visiteurs. Chaque niveau a son propre périmètre de communication et
d'administration.

| Rôle                       | Périmètre                   |
| -------------------------- | --------------------------- |
| Super administrateur       | Toute la plateforme         |
| Administrateur national    | Administration nationale    |
| Administrateur de district | Son district et ses églises |
| Administrateur d'église    | Son église locale           |

| Contenu              | National | District | Église |
| -------------------- | :------: | :------: | :----: |
| Bannières            |    ✓     |    ✓     |   ✓    |
| Actualités           |    ✓     |    ✓     |   ✓    |
| Événements           |    ✓     |    ✓     |   ✓    |
| Inscriptions         |    ✓     |    ✓     |   ✓    |
| Notifications        |    ✓     |    ✓     |   ✓    |
| Verset du jour       |    ✓     |    —     |   —    |
| Découvrir Foursquare |    ✓     |    —     |   —    |

Chaque droit est vérifié côté serveur, quel que soit l'écran utilisé.

## Application mobile

- **Personnalisation** : choix du district et de l'église au premier lancement, mémorisé et
  modifiable, ou mode visiteur.
- **Accueil** : bannières, dernières actualités, événements à venir, verset du jour, accès à
  l'annuaire et à Découvrir Foursquare.
- **Actualités** : nationales, du district et de l'église, filtrables et partageables
  (WhatsApp).
- **Événements** : à venir et passés, détail, inscription sans compte, partage.
- **Mon église** : présentation, pasteur, horaires des cultes, actualités et événements locaux,
  contacts et itinéraire.
- **Annuaire national** : districts et églises, recherche, fiches détaillées, localisation.
- **Notifications push** : nationales ou ciblées par district ou église, avec historique et
  ouverture du contenu associé.
- **Verset du jour** : publication quotidienne nationale, historique, partage.
- **Découvrir Foursquare** : histoire, vision, doctrine, bureau et organisation nationale.
- **Paramètres** : rattachement, confidentialité et gestion des consentements, à propos,
  version.

## Panel d'administration

Panel web statique servi sur `/admin/`, connecté à l'API.

| Module               | Rôle                                                      |
| -------------------- | --------------------------------------------------------- |
| Tableau de bord      | Statistiques et indicateurs                               |
| Districts, Églises   | Structure et fiches des églises                           |
| Actualités           | Création, modification, publication                       |
| Événements           | Programmes et événements                                  |
| Inscriptions         | Participants et export CSV                                |
| Bannières            | Affiches et annonces                                      |
| Notifications        | Envoi ciblé immédiat et historique                        |
| Versets du jour      | Saisie et programmation                                   |
| Découvrir Foursquare | Contenus institutionnels                                  |
| Médias               | Images et fichiers                                        |
| Confidentialité      | Demandes relatives aux données, journal des consentements |
| Administrateurs      | Comptes, rôles et périmètres                              |
| Paramètres           | Configuration générale                                    |

Les actions administratives sont tracées dans un journal d'audit côté serveur. Il n'a pas encore
d'écran dans le panel.

## Architecture technique

```text
Application Flutter ─┐
                     ├─► API REST Laravel ─► MariaDB / MySQL
Panel web ───────────┘          │
                                └─► Firebase Cloud Messaging (notifications)
```

| Technologie     | Utilisation                                   |
| --------------- | --------------------------------------------- |
| Laravel 13      | Backend et API REST (`/api/v1`)               |
| PHP 8.4         | Langage backend                               |
| Laravel Sanctum | Authentification des administrateurs          |
| MariaDB / MySQL | Base de données                               |
| Firebase FCM    | Notifications push (HTTP v1)                  |
| Flutter / Dart  | Application mobile (Riverpod, GoRouter)       |
| Docker          | Environnement de développement local          |
| GitHub Actions  | Tests puis déploiement à chaque push sur main |
| LWS cPanel      | Hébergement de production (mutualisé)         |

Les lectures publiques sont mises en cache 60 secondes et invalidées à chaque modification de
contenu, pour tenir les pics d'ouverture de l'application sur un hébergement mutualisé.

## Démarrer en local

Avec Docker (API et panel sur <http://127.0.0.1:8000>, base MariaDB incluse) :

```bash
docker compose up -d --build
```

Le guide complet, y compris le test de l'application sur un téléphone, est dans
[DOCKER.md](DOCKER.md).

Vérifications à lancer avant chaque envoi :

```bash
php artisan test              # tests Pest
vendor/bin/pint --dirty       # style PHP
vendor/bin/phpstan analyse app
npm run check                 # format des fichiers web et Markdown
```

## Documentation

| Fichier                                              | Contenu                                         |
| ---------------------------------------------------- | ----------------------------------------------- |
| [Docs/API.md](Docs/API.md)                           | Référence de l'API                              |
| [Docs/API_FRONTEND.md](Docs/API_FRONTEND.md)         | Intégration côté application Flutter            |
| [Docs/DEPLOYMENT.md](Docs/DEPLOYMENT.md)             | Mise en production sur LWS                      |
| [Docs/docfcm.md](Docs/docfcm.md)                     | Notifications push Firebase                     |
| [Docs/LEGAL_PUBLIC_APP.md](Docs/LEGAL_PUBLIC_APP.md) | Textes juridiques et conformité ARTCI (modèles) |
| [Docs/ia.md](Docs/ia.md)                             | Reprise du projet par un assistant IA           |

Aucun mot de passe, clé Firebase ou secret de production ne doit être ajouté au dépôt : ils
restent dans le `.env` du serveur et dans les secrets GitHub.

## Stratégie de lancement

1. **Phase pilote** dans une église, pour recueillir les retours en conditions réelles.
2. **Extension au district** de cette église et formation des administrateurs.
3. **Déploiement national progressif**, district par district.
4. **Déploiement national complet**.

## Hors périmètre V1

Volontairement reportés : comptes membres, finances (dîmes, offrandes, Mobile Money),
billetterie et QR codes, départements et ministères, prédications audio et vidéo, demandes de
prière, méditations, messagerie interne, streaming des cultes, programme hebdomadaire avancé.

## État du projet

| Composant                    | État                                          |
| ---------------------------- | --------------------------------------------- |
| Backend, API REST, panel     | Développés et déployés                        |
| Verset du jour, Découvrir    | Développés                                    |
| Confidentialité (API, panel) | Développée ; textes juridiques à compléter    |
| Application Flutter          | Développée ; écrans de consentement à ajouter |
| Recette technique finale     | À faire                                       |
| Lancement pilote             | Prochaine étape                               |
| Publication sur les stores   | À faire                                       |

Le périmètre fonctionnel de la V1.0 est **gelé** : la priorité est la stabilité, la sécurité,
la qualité des contenus et l'adoption par les fidèles.
