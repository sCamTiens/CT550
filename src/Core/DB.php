<?php
namespace App\Core;
use PDO;

class DB
{
    private static ?PDO $pdo = null;
    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $_ENV['DB_HOST'], $_ENV['DB_NAME']);
            self::$pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return self::$pdo;
    }

     public static function getConnection(): PDO {
        return self::pdo();
    }
}
