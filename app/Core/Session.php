<?php
namespace App\Core;

/**
 * Thin wrapper around PHP session handling with helper utilities.
 */
class Session
{
    /**
     * Starts the session when the application boots.
     */
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Stores a value in the session using dot notation keys.
     */
    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $ref = &$_SESSION;
        foreach ($segments as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }
        $ref = $value;
    }

    /**
     * Retrieves a session value or returns the provided default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $_SESSION;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    /**
     * Removes a value from the session.
     */
    public static function remove(string $key): void
    {
        $segments = explode('.', $key);
        $ref = &$_SESSION;
        while (count($segments) > 1) {
            $segment = array_shift($segments);
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                return;
            }
            $ref = &$ref[$segment];
        }
        unset($ref[array_shift($segments)]);
    }

    /**
     * Retrieves and simultaneously removes a flash value from the session.
     */
    public static function flash(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);
        self::remove($key);
        return $value;
    }

    /**
     * Checks whether a given key exists in the session.
     */
    public static function has(string $key): bool
    {
        return self::get($key, '__missing__') !== '__missing__';
    }
}