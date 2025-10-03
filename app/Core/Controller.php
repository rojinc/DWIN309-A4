<?php
namespace App\Core;

use App\Services\AuthService;

/**
 * Provides shared functionality to all feature controllers.
 */
abstract class Controller
{
    protected View $view;
    protected AuthService $auth;

    /**
     * Sets up the view renderer and authentication service.
     */
    public function __construct()
    {
        $this->view = new View();
        $this->auth = new AuthService();
    }

    /**
     * Convenience wrapper for rendering views through the shared renderer.
     */
    protected function render(string $template, array $data = [], string $layout = 'main'): void
    {
        $this->view->render($template, $data, $layout);
    }

    /**
     * Redirects the user to a different page and halts execution.
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Ensures the current session is authenticated, redirecting to login if required.
     */
    protected function requireAuth(): void
    {
        if (!$this->auth->check()) {
            $this->redirect('index.php?page=auth&action=login');
        }
    }

    /**
     * Restricts access to roles supplied in the whitelist.
     */
    protected function requireRole(array $roles): void
    {
        $this->requireAuth();
        if (!in_array($this->auth->userRole(), $roles, true)) {
            $this->redirect('index.php?page=dashboard&action=forbidden');
        }
    }

    /**
     * Stores a flash message for display on the next request.
     */
    protected function flash(string $type, string $message): void
    {
        $flash = Session::get('flash', []);
        $flash[$type] = $message;
        Session::set('flash', $flash);
    }
}