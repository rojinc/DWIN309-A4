<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * Centralises database bootstrap and provides reusable PDO connection.
 */
class Database
{
    private static ?PDO $connection = null;

    /**
     * Creates the PDO instance using configuration supplied at runtime.
     */
    public static function init(array $config): void
    {
        if (self::$connection !== null) {
            return;
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            self::$connection = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $exception) {
            error_log('Database connection failed: ' . $exception->getMessage());
            throw $exception;
        }
    }

    /**
     * Returns the active PDO connection.
     */
    public static function connection(): PDO
    {
        if (self::$connection === null) {
            throw new \RuntimeException('Database connection not initialised.');
        }
        return self::$connection;
    }
}