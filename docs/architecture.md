# Architecture applicative FIDEST

Le projet suit désormais une architecture progressive inspirée des frameworks PHP modernes.

## Couches

- `src/Domain` : modèles métier et repositories par entité.
- `src/Application` : services/cas d’usage orchestrant les règles métier.
- `src/Http/Controller` : contrôleurs recevant une `Request` et retournant une `Response`.
- `src/Infrastructure` : base de données, migrations, rendu des vues et conteneur de dépendances.
- `views` : vues et composants réutilisables.
- `routes/web.php` : déclaration centralisée des routes.
- `database/migrations/entities` : migrations idempotentes séparées par entité.
- `app.php` : front controller unique pour les URLs sans extension.
- `public/index.php` : cible du futur DocumentRoot Apache ; aucun nouveau module métier ne doit être ajouté à la racine.

## Routes principales

| URL | Module |
|---|---|
| `/dashboard` | Tableau de bord |
| `/clients` | Clients |
| `/appels-offres` | Appels d’offre |
| `/devis` | Devis |
| `/bons-commande` | Bons de commande |
| `/affaires` | Suivi du workflow |
| `/bons-livraison` | Bons de livraison |
| `/factures` | Factures |
| `/archives` | Exercices archivés |
| `/api/workflow/summary` | Synthèse financière JSON |

Les routes propres redirigent temporairement vers les pages historiques. Chaque page peut ainsi être remplacée progressivement par un contrôleur et une vue sans casser les anciens liens.

## Compatibilité et désencombrement de la racine

Les fichiers PHP historiques présents à la racine sont désormais considérés comme des points d’entrée de compatibilité. Ils ne doivent plus recevoir de logique métier nouvelle. Un module migré conserve au besoin un fichier racine de quelques lignes, tandis que son implémentation vit dans `src/` et `views/`.

Le module FEB applique déjà cette règle :

- `fiches_expression_besoin.php` est uniquement un adaptateur compatible ;
- `NeedRequestController` porte la couche HTTP ;
- `GetNeedRequestRegistry` et `ExportNeedRequestPdf` portent les cas d’usage ;
- `NeedRequestRepository` centralise les requêtes ;
- `views/need-requests/index.php` contient l’interface ;
- les migrations sont isolées dans `database/migrations/entities`.

Lorsque tous les modules historiques auront été migrés, le serveur pourra pointer directement sur `public/`. Les anciennes URL seront maintenues par le routeur jusqu’à leur retrait contrôlé.

## Ajouter une entité

1. Créer son modèle et son repository sous `src/Domain/<Entity>`.
2. Créer ses cas d’usage sous `src/Application/<Entity>`.
3. Ajouter son contrôleur sous `src/Http/Controller`.
4. Déclarer ses routes dans `routes/web.php`.
5. Ajouter une migration idempotente dédiée dans `database/migrations/entities`.

Le conteneur résout automatiquement les dépendances typées. Les dépendances primitives ou configurables doivent être déclarées dans `app_container()` dans `bootstrap.php`.
