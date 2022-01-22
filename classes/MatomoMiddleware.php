<?php namespace Renick\Matomo\Classes;

use Closure;
use Exception;
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
            Settings::instance()
                ->getBridge()
                ->useTracking();

        } catch (Exception $e) {
            // do nothing, it's quality code, I promise
        }
        return $next($request);
    }
}
