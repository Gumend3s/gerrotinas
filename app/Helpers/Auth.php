<?php

class Auth
{
    /**
     * Checks whether a user is currently authenticated.
     *
     * @return bool True if a user is logged in, false otherwise.
     */
    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION[APP_SESSION_KEY]['user_id']);
    }

    /**
     * Checks whether the logged-in user is authorized according to the given allowed levels array.
     *
     * @param  array $allowed_levels Array with allowed levels.
     * @return bool  True   if the user is authorized, false otherwise.
     */
    public static function hasAccess(array $allowed_levels): bool
    {
        if (!self::isLoggedIn() || empty($_SERVER['HTTP_REFERER'])) return false;

        if (self::canBypass()) return true;
        
        return in_array($_SESSION[APP_SESSION_KEY]['user_level'] ?? '', $allowed_levels);
    }

    /**
     * Determines whether the current user can bypass access level checks.
     *
     * @return bool True if the current user's level can bypass access checks.
     */
    public static function canBypass(): bool
    {
        return in_array($_SESSION[APP_SESSION_KEY]['user_level'], ['MASTER'], true);
    }

    /**
     * Generates a CSRF token and stores it in the session, reusing an existing token if one is already present.
     *
     * @return string The CSRF token.
     */
    public static function generateCsrf(): string
    {
        if (empty($_SESSION[APP_SESSION_KEY]['csrf_token'])) $_SESSION[APP_SESSION_KEY]['csrf_token'] = bin2hex(random_bytes(32));

        return $_SESSION[APP_SESSION_KEY]['csrf_token'];
    }

    /**
     * Validates a CSRF token against the one stored in the session.
     *
     * @param  string|null $token The token submitted by the client.
     * @return bool        True if the token is valid, false otherwise.
     */
    public static function validateCsrf(?string $token): bool
    {
        if (empty($token) || empty($_SESSION[APP_SESSION_KEY]['csrf_token'])) return false;

        return hash_equals($_SESSION[APP_SESSION_KEY]['csrf_token'], $token);
    }
}