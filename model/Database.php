<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

/** @deprecated Utiliser l'injection de PDO dans un Repository. */
final class Database
{
    public static function getConnection(): PDO
    {
        return app_database();
    }
}
