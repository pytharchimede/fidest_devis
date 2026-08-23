<?php

declare(strict_types=1);

define('APP_ROOT', __DIR__);

$environmentFile = APP_ROOT . '/.env';
if (is_file($environmentFile)) {
    foreach (file($environmentFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = array_map('trim', explode('=', $line, 2));
        if ($name !== '' && getenv($name) === false) {
            $value = trim($value, "\"'");
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $path = APP_ROOT . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

function app_database(): PDO
{
    static $migrationsApplied = false;
    $database = App\Infrastructure\Database\Connection::get();
    if (!$migrationsApplied) {
        app_run_migrations($database);
        $migrationsApplied = true;
    }
    return $database;
}

function app_run_migrations(PDO $database): void
{
    $database->exec(
        'CREATE TABLE IF NOT EXISTS app_migrations (' .
            'migration VARCHAR(255) NOT NULL PRIMARY KEY,' .
            'applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP' .
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $appliedStatement = $database->query('SELECT migration FROM app_migrations');
    $applied = array_flip($appliedStatement->fetchAll(PDO::FETCH_COLUMN));
    $migrationFiles = glob(APP_ROOT . '/database/migrations/*.sql') ?: [];
    sort($migrationFiles, SORT_STRING);

    foreach ($migrationFiles as $migrationFile) {
        $migration = basename($migrationFile);
        if (isset($applied[$migration])) {
            continue;
        }

        $sql = trim((string) file_get_contents($migrationFile));
        if ($sql === '') {
            continue;
        }

        $statements = preg_split('/;\s*(?:\R|$)/', $sql) ?: [];
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $database->exec($statement);
            }
        }

        $migrationStatement = $database->prepare(
            'INSERT INTO app_migrations (migration) VALUES (:migration)'
        );
        $migrationStatement->execute(['migration' => $migration]);
    }
}

function app_branding(): App\Infrastructure\Branding\Branding
{
    static $branding;
    return $branding ??= new App\Infrastructure\Branding\Branding();
}
