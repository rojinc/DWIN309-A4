<?php
if (!function_exists('e')) {
    /**
     * Escapes a string for safe HTML output.
     */
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('route')) {
    /**
     * Generates a URL to the supplied page and action with optional query params.
     */
    function route(string $page, string $action = 'index', array $params = []): string
    {
        $query = array_merge(['page' => $page, 'action' => $action], $params);
        return 'index.php?' . http_build_query($query);
    }
}

if (!function_exists('asset')) {
    /**
     * Builds the relative path to an asset stored in the public assets directory.
     */
    function asset(string $path): string
    {
        return 'assets/' . ltrim($path, '/');
    }
}

if (!function_exists('post')) {
    /**
     * Retrieves a POST value with optional trimming and fallback default.
     */
    function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }
}

if (!function_exists('redirect')) {
    /**
     * Performs a raw HTTP redirect.
     */
    function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}