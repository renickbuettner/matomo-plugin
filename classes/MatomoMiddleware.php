<?php namespace Renick\Matomo\Classes;

use Closure;
use Renick\Matomo\Classes\Compat\UserResolver;
use Throwable;
use Illuminate\Http\Request;
use Renick\Matomo\Models\Settings;

class MatomoMiddleware
{
    /**
     * Handle an incoming request.
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!in_array($request->method(), ['GET', 'POST', 'HEAD'], true)) {
            return $next($request);
        }

        try {
            if (UserResolver::isBackendUserLoggedIn()) {
                return $next($request);
            }

            Settings::instance()
                ->getBridge()
                ->useTracking();

        } catch (Throwable $e) {
            // tracking must never break the page
        }
        return $next($request);
    }
}
