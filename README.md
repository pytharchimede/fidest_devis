# FIDEST Devis

Application PHP de gestion des devis et documents commerciaux.

## Architecture

Le code métier suit une architecture orientée objet légère :

- `src/Domain` : repositories et règles liées aux clients, offres et devis ;
- `src/Application` : cas d’usage et orchestration des transactions ;
- `src/Infrastructure` : connexion PDO et services techniques ;
- `bootstrap.php` : autoloading PSR-4 simplifié et initialisation ;
- fichiers PHP publics et `request/` : points d’entrée HTTP fins.

Les anciennes classes de `model/` restent temporairement disponibles comme couche de compatibilité.

## Configuration

La connexion utilise les variables d’environnement suivantes :

```text
DB_HOST=localhost
DB_PORT=3306
DB_NAME=fidestci_app_db
DB_USER=fidest
DB_PASSWORD=change-me
DB_CHARSET=utf8mb4
```

Voir `.env.example`. Le serveur web doit injecter ces variables dans l’environnement PHP.

## Branding

Les valeurs officielles du branding sont centralisées dans `config/branding.php` puis exposées à toutes les feuilles de style par `branding.css.php`. Les règles d’utilisation sont documentées dans `docs/CHARTE_GRAPHIQUE.md`.
