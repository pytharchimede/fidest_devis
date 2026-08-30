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
    (new App\Infrastructure\Database\Migrator($database, APP_ROOT . '/database/migrations'))->run();
}

function app_branding(): App\Infrastructure\Branding\Branding
{
    static $branding;
    return $branding ??= new App\Infrastructure\Branding\Branding();
}

function app_container(): App\Infrastructure\Container\Container
{
    static $container;
    if ($container instanceof App\Infrastructure\Container\Container) { return $container; }
    $container = new App\Infrastructure\Container\Container();
    $container->singleton(PDO::class, static fn() => app_database());
    $container->singleton(App\Infrastructure\Branding\Branding::class, static fn() => app_branding());
    $container->singleton(App\Infrastructure\View\ViewRenderer::class, static fn() => new App\Infrastructure\View\ViewRenderer(APP_ROOT . '/views'));
    return $container;
}

function app_router(): App\Http\Router
{
    static $router;
    if ($router instanceof App\Http\Router) { return $router; }
    $scriptDirectory = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
    $router = new App\Http\Router(app_container(), $scriptDirectory === '/' ? '' : $scriptDirectory);
    $register = require APP_ROOT . '/routes/web.php';
    $register($router);
    return $router;
}
