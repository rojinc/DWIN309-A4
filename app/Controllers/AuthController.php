<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Csrf;
use App\Helpers\Validation;
use App\Core\Session;

/**
 * Handles user authentication workflows.
 */
class AuthController extends Controller
{
    /**
     * Presents and processes the login form.
     */
    public function loginAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify('login', post('csrf_token'))) {
                $this->flash('error', 'Security token mismatch. Please try again.');
                $this->redirect(route('auth', 'login'));
            }
            $validation = Validation::make($_POST, [
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);
            if ($validation['errors']) {
                $this->flash('error', implode(' ', $validation['errors']));
                $this->redirect(route('auth', 'login'));
            }
            $data = $validation['data'];
            if ($this->auth->attempt($data['email'], $data['password'])) {
                $this->flash('success', 'Welcome back!');
                $this->redirect(route('dashboard', 'index'));
            }
            $this->flash('error', 'Invalid credentials or account disabled.');
            $this->redirect(route('auth', 'login'));
        }

        $token = Csrf::token('login');
        $this->render('auth/login', [
            'csrfToken' => $token,
            'pageTitle' => 'Sign In',
        ], 'auth');
    }

    /**
     * Logs the user out and redirects to the login page.
     */
    public function logoutAction(): void
    {
        $this->auth->logout();
        Session::set('flash', ['success' => 'You have been signed out.']);
        $this->redirect(route('auth', 'login'));
    }
}