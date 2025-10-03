<?php
namespace App\Services;

use App\Core\Session;
use App\Models\UserModel;

/**
 * Coordinates authentication tasks including login, logout, and role checks.
 */
class AuthService
{
    private UserModel $users;

    /**
     * Prepares the user data access object for authentication operations.
     */
    public function __construct()
    {
        $this->users = new UserModel();
    }

    /**
     * Attempts to authenticate using provided credentials.
     */
    public function attempt(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        if (($user['status'] ?? 'active') !== 'active') {
            return false;
        }
        Session::set('auth.user', [
            'id' => (int) $user['id'],
            'role' => $user['role'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'branch_id' => $user['branch_id'] ?? null,
        ]);
        return true;
    }

    /**
     * Determines whether a user is currently authenticated.
     */
    public function check(): bool
    {
        return Session::has('auth.user');
    }

    /**
     * Returns the current authenticated user payload.
     */
    public function user(): ?array
    {
        return Session::get('auth.user');
    }

    /**
     * Retrieves the role of the logged-in user.
     */
    public function userRole(): ?string
    {
        $user = $this->user();
        return $user['role'] ?? null;
    }

    /**
     * Invalidates the session and logs the user out.
     */
    public function logout(): void
    {
        Session::remove('auth.user');
    }
}