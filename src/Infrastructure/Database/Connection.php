<?php
declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class Connection
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function get(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        /** @var array{host:string,port:string,name:string,user:string,password:string,charset:string} $config */
        $config = require APP_ROOT . '/config/database.php';
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'], $config['port'], $config['name'], $config['charset']
        );

        self::$instance = new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$instance;
    }
}
