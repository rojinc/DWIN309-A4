<?php
namespace App\Core;

/**
 * Provides immutable access to configuration values loaded at bootstrap.
 */
class Config
{
    /** @var array<string, mixed> */
    private static array $config = [];

    /**
     * Seeds the configuration repository with application settings.
     */
    public static function init(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Retrieves a configuration value using dot notation (e.g. `app.name`).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$config;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}