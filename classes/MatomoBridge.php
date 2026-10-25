<?php namespace Renick\Matomo\Classes;

use MatomoTracker;
use Renick\Matomo\Classes\Compat\UserResolver;

class MatomoBridge
{
    private $tracker;
    private bool $hasToken;

    public function __construct(string $url, ?string $token, int $siteId)
    {
        $this->tracker = new MatomoTracker($siteId, $url);
        $this->hasToken = (string) $token !== '';
        $this->hasToken && $this->tracker->setTokenAuth($token);

        // less payload, less time consumed
        $this->tracker->disableSendImageResponse();

        // tracker 4.x throws on failed requests by default; 3.x returns false (and has no such switch)
        method_exists($this->tracker, 'setExceptionsEnabled') && $this->tracker->setExceptionsEnabled(false);
    }

    /**
     * Call Matomo tracking event
     * @param string $pageTitle (optional) page title if existing
     * @return void
     */
    public function useTracking(string $pageTitle = ''): void
    {
        // do we have an authenticated user?
        if ($user = UserResolver::frontendUser()) {
            $userId = $user->email ?? $user->id ?? null;
            $userId && $this->tracker->setUserId($userId);
        }

        // overriding the ip needs a token; x_forwarded_for can be set freely by the client
        // see https://stackoverflow.com/a/3003233
        if ($this->hasToken && ($ip = $this->clientIp())) {
            $this->tracker->setIp($ip);
        }

        $this->tracker->doTrackPageView($pageTitle);
    }

    protected function clientIp(): string
    {
        $chain = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        $chain[] = $_SERVER['REMOTE_ADDR'] ?? '';

        foreach ($chain as $ip) {
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '';
    }
}
