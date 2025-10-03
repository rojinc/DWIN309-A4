<?php
/**
 * Application bootstrap: loads configuration, sets up autoloading, and initialises core services.
 */
$config = require __DIR__ . '/config.php';

date_default_timezone_set($config['app']['timezone']);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/Helpers/helpers.php';

App\Core\Session::init();
App\Core\Database::init($config['db']);
App\Core\Config::init($config);