# Pages juridiques publiques — Foursquare Côte d’Ivoire

Version de référence : **27 novembre 2026**.

Les textes destinés au public sont entièrement rédigés dans les fichiers suivants :

| Page                                | Source                       | URL publique |
| ----------------------------------- | ---------------------------- | ------------ |
| Politique de confidentialité        | `resources/legal/privacy.md` | `/privacy`   |
| Politique cookies et stockage local | `resources/legal/cookies.md` | `/cookies`   |
| Mentions légales                    | `resources/legal/legal.md`   | `/legal`     |
| Conditions d’utilisation            | `resources/legal/terms.md`   | `/terms`     |

Ces quatre pages sont rendues en HTML par `LegalPageController`, accessibles sans authentification et reliées entre elles par la navigation située sous chaque texte. Les fichiers placés dans `resources/legal` constituent la source officielle à modifier : le présent document ne duplique pas leur contenu afin d’éviter qu’une ancienne version reste publiée par erreur.

## Textes courts à afficher dans l’application

### Choix de l’église de référence

> Le choix d’une église est facultatif. Il sert à personnaliser les contenus et notifications et peut révéler une conviction ou une activité religieuse. En sélectionnant « Confirmer », vous consentez à enregistrer ce choix et à l’associer à votre appareil. Vous pourrez le modifier ou le supprimer à tout moment. Consultez la Politique de confidentialité.

Actions proposées : **Confirmer** et **Plus tard**. Aucun choix ne doit être précoché.

### Notifications

> Souhaitez-vous recevoir les actualités, événements et messages correspondant à vos choix ? Les notifications sont facultatives et peuvent être désactivées à tout moment dans l’application ou dans les réglages du téléphone.

L’autorisation système ne doit être demandée qu’après l’accord exprimé sur cet écran.

### Inscription à un événement

> Les informations obligatoires sont utilisées uniquement pour gérer votre inscription à cet événement. L’église et l’adresse e-mail restent facultatives lorsqu’elles sont indiquées comme telles. Les administrateurs autorisés de l’événement pourront consulter ces informations. Vous pouvez exercer vos droits depuis Paramètres > Confidentialité > Exercer mes droits.

Case obligatoire non précochée :

> **J’ai lu la Politique de confidentialité et je demande mon inscription à cet événement.**

Si l’église est facultative, son enregistrement doit faire l’objet d’un consentement distinct de la demande d’inscription.

## Rubrique Paramètres

La rubrique **Paramètres > À propos et confidentialité** doit donner accès à :

- Politique de confidentialité ;
- Cookies et stockage local ;
- Mentions légales ;
- Conditions d’utilisation ;
- gestion de l’église de référence ;
- activation ou désactivation des notifications ;
- formulaire « Exercer mes droits ».

Le formulaire d’exercice des droits utilise `POST /api/v1/privacy/data-requests`. Les consentements et retraits utilisent respectivement `POST /api/v1/privacy/consents` et `POST /api/v1/privacy/consents/withdrawals`.

## Contrôles avant publication

- vérifier que les quatre URL HTTPS répondent sans connexion ;
- vérifier que l’application ouvre ces URL dans sa rubrique Paramètres et dans les formulaires concernés ;
- confirmer que les coordonnées, prestataires et pratiques décrits correspondent toujours à la production ;
- réviser la version affichée lors de toute modification substantielle ;
- faire valider les textes et les durées de conservation par le responsable de l’organisation et, si nécessaire, par un conseil juridique ;
- accomplir les déclarations ou autorisations requises auprès de l’ARTCI avant le traitement de données sensibles ou tout transfert international soumis à autorisation.
