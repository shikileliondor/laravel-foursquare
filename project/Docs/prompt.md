# Développement de l'application Foursquare Côte d'Ivoire — V1 nationale

## 1. Contexte du projet

Je développe actuellement une application mobile destinée aux Églises Foursquare de Côte d'Ivoire.

Le projet existe déjà et dispose d'une interface mobile fonctionnelle affichant actuellement des données de démonstration.

Stack technique :

- Application mobile : Flutter.
- Backend et API REST : Laravel.
- Base de données : MySQL.
- Authentification administrative : Laravel Sanctum ou système existant.
- Notifications push : Firebase Cloud Messaging.
- Back-office web : conserver la technologie et l'architecture existantes.

L'objectif est de développer une application à vocation nationale, dont le premier lancement se fera au sein de l'église Foursquare Niangon Révélation.

Attention : il ne s'agit pas de développer une application exclusivement destinée à Niangon Révélation. L'application doit être nationale dès sa conception.

## 2. Consignes avant toute modification

Commence par analyser intégralement le projet existant :

- Architecture Flutter.
- Architecture Laravel.
- Modèles et migrations.
- Base de données.
- Routes API.
- Contrôleurs et services.
- Système d'authentification.
- Interfaces existantes.
- Gestion des permissions.
- Configuration Firebase.
- Données de démonstration.
- Problèmes de communication entre l'application mobile et le serveur.

Identifie les fonctionnalités existantes, celles qui sont incomplètes et celles qui doivent être corrigées.

Ne recrée pas le projet.
Ne supprime pas arbitrairement les fonctionnalités existantes.
Réutilise les composants et services déjà développés.
Évite les dépendances inutiles.

Présente un état des lieux et un plan d'implémentation avant d'entreprendre les modifications importantes.

## 3. Vision générale

Nom de l'application : **Foursquare Côte d'Ivoire**.

L'application doit centraliser :

- Les actualités du mouvement.
- Les communications officielles.
- Les événements et programmes.
- Les informations des districts.
- L'annuaire des églises.
- Les notifications.

Le développement doit couvrir le volet national, mais le premier lancement et la communication autour de l'application se feront à Foursquare Niangon Révélation.

L'ouverture aux autres assemblées se fera progressivement, sans nécessiter une nouvelle application ou une refonte majeure.

## 4. Organisation nationale

La plateforme repose sur trois niveaux :

```text
Foursquare Côte d'Ivoire
│
├── District de la Côtière
│   ├── Foursquare Niangon Révélation
│   └── Autres églises
│
├── Autres districts
│   └── Églises locales
│
└── Autres districts
    └── Églises locales
```

Il ne doit pas y avoir de niveau Zone dans l'architecture fonctionnelle.

Chaque église est directement rattachée à un district.

Prévoir la possibilité d'activer ou de désactiver les districts et les églises.

Si le niveau Zone existe déjà dans le code, analyser ses dépendances et prévoir une migration sécurisée avant sa suppression.

## 5. Application mobile

Conserver l'identité visuelle actuelle, le logo Foursquare, les couleurs, les composants graphiques et la navigation existante.

La navigation principale doit conserver quatre onglets :

- Accueil.
- Actus.
- Événements.
- Églises.

L'accès à l'application et la consultation des informations doivent être possibles sans création de compte.

### 5.1. Accueil

Conserver la structure actuelle :

- En-tête avec le logo et le nom Foursquare Côte d'Ivoire.
- Icône de notifications.
- Carrousel des informations à la une.
- Prochain événement.
- Dernières actualités.

Les données doivent provenir de l'API Laravel.

Prévoir une distinction visuelle simple entre les publications :

- National.
- District.
- Église locale.

Permettre à l'utilisateur de sélectionner son église de référence, sans obligation de créer un compte.

Lorsqu'une église est sélectionnée, afficher prioritairement :

- Les informations nationales.
- Les informations de son district.
- Les informations de son église.

Sans église sélectionnée, privilégier les informations nationales.

Prévoir la possibilité de changer d'église ultérieurement.

### 5.2. Actualités

Afficher les actualités publiées sur la plateforme.

Chaque actualité doit pouvoir contenir :

- Titre.
- Image principale.
- Résumé.
- Contenu détaillé.
- Catégorie.
- Date de publication.
- Source de publication.
- Niveau de publication.
- Statut de publication.
- Mise à la une.

Prévoir des filtres simples :

- Toutes.
- National.
- District.
- Mon église.

Permettre la consultation détaillée et le partage d'une actualité, notamment sur WhatsApp.

Les brouillons et les actualités non publiées ne doivent jamais être accessibles publiquement.

### 5.3. Événements

Permettre la consultation des événements nationaux, des districts et des églises locales.

Informations d'un événement :

- Titre.
- Affiche.
- Description.
- Catégorie.
- Organisateur.
- Date de début.
- Date de fin.
- Heure.
- Lieu.
- Niveau de publication.
- Contact.
- Statut.
- Inscription activée ou non.

Prévoir :

- Événements à venir.
- Événements passés.
- Page de détails.
- Partage de l'événement.
- Inscription lorsque celle-ci est nécessaire.

L'inscription doit rester simple et accessible sans compte utilisateur.

Champs proposés :

- Nom et prénoms.
- Numéro de téléphone.
- Adresse e-mail facultative.
- Église de provenance, si nécessaire.

Le formulaire doit être activable ou désactivable pour chaque événement.

Les administrateurs autorisés doivent pouvoir consulter et exporter les inscriptions.

### 5.4. Églises

Créer un annuaire national des églises Foursquare.

Permettre :

- La recherche d'une église par son nom.
- Le filtrage par district.
- La consultation des églises actives.
- La sélection d'une église comme église de référence.

Chaque église dispose d'une fiche contenant :

- Nom de l'église.
- District.
- Photo.
- Présentation.
- Nom du responsable pastoral.
- Ville.
- Commune.
- Adresse.
- Localisation GPS, si disponible.
- Contacts.
- Horaires des cultes.
- Réseaux sociaux, si disponibles.

Afficher uniquement les informations renseignées et autorisées à la publication.

### 5.5. Notifications

Utiliser Firebase Cloud Messaging.

Prévoir trois niveaux de diffusion :

- Notifications nationales.
- Notifications de district.
- Notifications locales.

Un utilisateur ayant sélectionné une église peut recevoir les notifications nationales, celles de son district et celles de son église.

Gérer correctement les abonnements et désabonnements lorsqu'un utilisateur change d'église.

Prévoir un historique des notifications dans l'application et une redirection vers l'actualité ou l'événement concerné.

## 6. Administration web

Développer ou adapter le back-office existant afin de gérer l'application au niveau national.

### Tableau de bord

Afficher des statistiques simples :

- Nombre de districts.
- Nombre d'églises.
- Nombre d'églises actives.
- Nombre d'actualités.
- Nombre d'événements.
- Nombre d'inscriptions.
- Dernières publications.

Les statistiques doivent respecter le périmètre de chaque administrateur.

### Modules

- Tableau de bord.
- Districts.
- Églises.
- Actualités.
- Événements.
- Inscriptions.
- Notifications.
- Administrateurs.
- Paramètres.

Conserver une interface professionnelle, simple, responsive et cohérente avec l'identité visuelle Foursquare.

## 7. Rôles et permissions

Prévoir quatre rôles principaux.

### Super administrateur

- Administration technique globale.
- Configuration de la plateforme.
- Gestion des administrateurs.
- Gestion des permissions.
- Accès à l'ensemble des modules.

### Administrateur national

- Gestion des districts.
- Gestion des églises.
- Gestion des administrateurs territoriaux.
- Publication des actualités nationales.
- Publication des événements nationaux.
- Envoi des notifications nationales.
- Consultation des statistiques nationales.

### Administrateur de district

- Gestion des informations de son district.
- Gestion des églises de son district selon ses permissions.
- Publication des actualités de son district.
- Publication des événements de son district.
- Envoi des notifications de district.
- Consultation des statistiques de son district.

### Administrateur d'église

- Gestion des informations de son église.
- Publication des actualités locales.
- Publication des événements locaux.
- Consultation des inscriptions locales.
- Envoi des notifications locales.
- Consultation des statistiques locales.

Un administrateur d'église ne doit jamais pouvoir modifier ou supprimer les données d'une autre église.

Un administrateur de district ne doit jamais pouvoir intervenir dans un autre district.

Appliquer les restrictions directement dans le backend et non uniquement dans l'interface graphique.

Utiliser les Policies, Gates, middlewares et permissions Laravel adaptés.

## 8. Structure de la base de données

Analyser les tables existantes et adapter le schéma sans créer de doublons inutiles.

Entités principales proposées :

- users
- roles
- permissions
- districts
- churches
- news
- news_categories
- events
- event_categories
- event_registrations
- notifications
- device_tokens
- settings

Relations principales :

- Un district possède plusieurs églises.
- Une église appartient à un district.
- Un administrateur peut être rattaché à un périmètre national, à un district ou à une église.
- Une actualité possède un auteur et un périmètre de publication.
- Un événement possède un organisateur et un périmètre de publication.
- Un événement possède plusieurs inscriptions.
- Une notification possède un périmètre de diffusion.

Pour les actualités, événements et notifications, prévoir une gestion cohérente de la portée :

- national
- district
- church

Utiliser les relations et champs nécessaires, notamment :

- scope
- district_id
- church_id
- created_by

Garantir la cohérence entre le niveau de publication et les rattachements correspondants.

Utiliser des clés étrangères, des index, des contraintes d'intégrité et des validations appropriées.

Ne pas supprimer de données existantes sans sauvegarde et validation préalable.

## 9. API REST

Conserver les conventions de l'API existante.

Prévoir notamment les endpoints publics suivants, à adapter selon les routes déjà présentes :

```text
GET /api/v1/home
GET /api/v1/districts
GET /api/v1/churches
GET /api/v1/churches/{id}
GET /api/v1/news
GET /api/v1/news/{id}
GET /api/v1/events
GET /api/v1/events/{id}
GET /api/v1/notifications

POST /api/v1/events/{id}/register
POST /api/v1/devices/register
PUT /api/v1/devices/preferences
```

Prévoir :

- Pagination.
- Recherche.
- Filtres.
- Laravel API Resources.
- Validation avec Form Requests.
- Gestion centralisée des erreurs.
- Rate limiting.
- Réponses JSON cohérentes.
- Gestion des dates et fuseaux horaires.
- URLs d'images valides.
- Documentation des endpoints.

L'API publique doit retourner uniquement les contenus publiés et autorisés.

Les endpoints administratifs doivent être protégés par authentification et autorisation.

## 10. Connexion entre Flutter et Laravel

Actuellement, l'application affiche un message indiquant que le serveur est injoignable et utilise des données de démonstration.

Identifier précisément l'origine du problème :

- URL de base de l'API.
- Configuration des environnements.
- Accessibilité du serveur.
- Routes Laravel.
- Certificat HTTPS.
- Erreurs HTTP.
- Format des réponses JSON.
- Configuration Android et iOS.
- Timeouts.
- Configuration de la base de données.
- Logs Laravel.

Corriger le problème de communication.

Les données de démonstration doivent être réservées à un environnement de développement explicitement configuré.

En production, ne jamais présenter de fausses actualités ou de faux événements comme des informations officielles.

Prévoir :

- Indicateurs de chargement.
- Skeleton loaders.
- États vides.
- Gestion des erreurs réseau.
- Bouton de réessai.
- Pull-to-refresh.
- Mise en cache des dernières données réellement récupérées.

## 11. Configuration du premier lancement

Préparer le lancement à Foursquare Niangon Révélation.

Créer ou compléter les seeders nécessaires pour :

- Le district de la Côtière.
- L'église Foursquare Niangon Révélation.
- Les rôles et permissions.
- Les catégories d'actualités.
- Les catégories d'événements.

Créer un mécanisme d'initialisation sécurisé des comptes administratifs.

Ne pas inventer les noms des autres districts, les informations officielles, les contacts ou les responsables pastoraux.

Ne pas coder en dur Niangon Révélation dans l'architecture générale.

L'église doit être configurable depuis le back-office.

Prévoir une intégration progressive des autres églises, sans modification du code source ni nouvelle publication de l'application mobile.

## 12. Sécurité et confidentialité

Appliquer les bonnes pratiques de sécurité :

- Authentification sécurisée des administrateurs.
- Contrôle des permissions côté serveur.
- Protection contre les accès non autorisés aux ressources.
- Validation et nettoyage des données entrantes.
- Protection contre les injections SQL.
- Protection contre les inscriptions automatisées abusives.
- Validation des fichiers téléversés.
- Limitation des requêtes.
- Protection des données personnelles des participants.
- Journalisation des actions administratives importantes.
- Gestion sécurisée des secrets et variables d'environnement.

Ne jamais exposer publiquement les coordonnées des participants aux événements.

Ne jamais inclure de mots de passe réels, de clés Firebase privées ou de secrets de production dans le dépôt Git.

## 13. Périmètre strict de la V1

Ne pas développer pour le moment :

- Gestion des membres.
- Gestion des départements.
- Gestion financière.
- Dîmes et offrandes en ligne.
- Messagerie interne.
- Réseau social.
- Gestion des présences.
- Espace personnel complexe.
- Demandes de prière.
- Intelligence artificielle.
- Streaming intégré.
- Gestion des zones.

La V1 doit rester une plateforme nationale d'information, de communication, d'événements et de découverte des églises.

Ne propose pas de fonctionnalités supplémentaires sans justification et validation.

## 14. Tests

Prévoir des tests fonctionnels et techniques couvrant notamment :

- Consultation publique sans compte.
- Création d'un district.
- Création d'une église.
- Activation et désactivation d'une église.
- Publication d'une actualité nationale.
- Publication d'une actualité de district.
- Publication d'une actualité locale.
- Filtrage des publications.
- Création d'un événement.
- Inscription à un événement.
- Export des inscriptions.
- Changement d'église de référence.
- Réception des notifications.
- Cloisonnement des permissions.
- Tentatives d'accès aux données d'une autre église.
- Gestion des erreurs réseau.

Exécuter les tests disponibles, Laravel Pint, Flutter Analyze et les vérifications de compilation adaptées au projet.

## 15. Méthode de travail

Procéder dans cet ordre :

1. Audit du projet existant.
2. Identification des anomalies.
3. Analyse de la base de données et des relations.
4. Simplification de la hiérarchie nationale.
5. Correction du backend et des API.
6. Mise en place des permissions.
7. Adaptation du back-office.
8. Connexion de Flutter aux données réelles.
9. Gestion des notifications.
10. Configuration de Niangon Révélation.
11. Tests fonctionnels et de sécurité.
12. Documentation et préparation du déploiement.

Travailler progressivement, en conservant les fonctionnalités opérationnelles.

Ne pas exécuter de commandes destructives sur la base de données de production.

Ne pas déployer automatiquement en production sans mon autorisation.

## 16. Résultat attendu

À la fin du développement, je dois disposer :

- D'une application mobile Foursquare Côte d'Ivoire fonctionnelle.
- D'une architecture nationale évolutive.
- D'un backend Laravel sécurisé.
- D'un back-office opérationnel.
- D'une gestion des districts et des églises.
- D'un système de publication à trois niveaux.
- D'un système d'événements et d'inscriptions.
- D'un système de notifications.
- D'une application connectée aux données réelles.
- D'une configuration permettant le premier lancement à Foursquare Niangon Révélation.

Fournis également un récapitulatif des fichiers modifiés, des migrations créées, des tests exécutés, des anomalies corrigées et des éventuels points restant à traiter.

Commence maintenant par analyser le projet existant et établir un plan d'intervention adapté à son état réel.

La philosophie du projet est simple : **développer nationalement, lancer localement et étendre progressivement.**
