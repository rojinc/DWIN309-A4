<?php
namespace App\Helpers;

use App\Core\Session;

/**
 * Generates and validates CSRF tokens for form submissions.
 */
class Csrf
{
    /**
     * Produces a token for the given form key and stores it in the session.
     */
    public static function token(string $formKey): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set('csrf.' . $formKey, $token);
        return $token;
    }

    /**
     * Confirms that the posted token matches the stored session token.
     */
    public static function verify(string $formKey, ?string $token): bool
    {
        $stored = Session::get('csrf.' . $formKey);
        Session::remove('csrf.' . $formKey);
        return is_string($token) && is_string($stored) && hash_equals($stored, $token);
    }
}