<?php
require __DIR__ . '/app/bootstrap.php';

$router = new App\Core\Router();
$router->dispatch();