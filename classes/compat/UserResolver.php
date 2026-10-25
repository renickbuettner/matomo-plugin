<?php namespace Renick\Matomo\Classes\Compat;

use Throwable;

/**
 * Adapter for user lookups; October 1.x - 4.x and RainLab.User differ in facade API.
 */
class UserResolver
{
    public static function backendUser()
    {
        return self::resolve('BackendAuth', 'Backend\Facades\BackendAuth');
    }

    public static function frontendUser()
    {
        return self::resolve('Auth', 'RainLab\User\Facades\Auth');
    }

    public static function isBackendUserLoggedIn(): bool
    {
        return self::backendUser() !== null;
    }

    protected static function resolve(string ...$facades)
    {
        foreach ($facades as $facade) {
            try {
                if (!class_exists($facade)) {
                    continue;
                }

                $root = $facade::getFacadeRoot();
                if (is_object($root) && method_exists($root, 'check') && method_exists($root, 'getUser')) {
                    return $root->check() ? ($root->getUser() ?: null) : null;
                }

                if (is_object($root) && method_exists($root, 'getUser')) {
                    return $root->getUser() ?: null;
                }
            } catch (Throwable $e) {
                // facade without a usable guard on this October version, try the next one
            }
        }

        return null;
    }
}
