<?php
namespace App\Core;

/**
 * Resolves incoming requests to controller actions using the query string.
 */
class Router
{
    /**
     * Parses request parameters, invokes the matching controller action, and handles 404s.
     */
    public function dispatch(): void
    {
        $page = $_GET['page'] ?? 'home';
        $action = $_GET['action'] ?? 'index';
        $controllerClass = 'App\\Controllers\\' . ucfirst($page) . 'Controller';
        $method = $action . 'Action';

        if (!class_exists($controllerClass)) {
            $this->renderNotFound($page, $action);
            return;
        }

        $controller = new $controllerClass();
        if (!method_exists($controller, $method)) {
            $this->renderNotFound($page, $action);
            return;
        }

        $controller->$method();
    }

    /**
     * Produces a minimal 404 response while keeping the page styled.
     */
    private function renderNotFound(string $page, string $action): void
    {
        http_response_code(404);
        (new View())->render('partials/404', [
            'page' => $page,
            'action' => $action,
            'pageTitle' => 'Page Not Found'
        ]);
    }
}