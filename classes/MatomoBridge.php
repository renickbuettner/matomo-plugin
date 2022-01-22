<?php namespace Renick\Matomo\Classes;

use BackendAuth;
use MatomoTracker;

class MatomoBridge
{
    private $tracker;

    public function __construct(string $url, string $token, int $siteId)
    {
        $this->tracker = new MatomoTracker($siteId, $url);
        $this->tracker->setTokenAuth($token);

        // less payload, less time consumed
        $this->tracker->disableSendImageResponse();
    }

    /**
     * Call Matomo tracking event
     * @param string $pageTitle (optional) page title if existing
     * @return void
     */
    public function useTracking(string $pageTitle = ""): void
    {
        // do we have an authenticated user?
        if(class_exists('Auth') && \Auth::check() && $user = \Auth::getUser()){
            $userId = $user->email ?? $user->id ?? null;
            $userId && $this->tracker->setUserId($userId);
        }

        // catch client ip address, but mind that x_forwared_for can be set freely by the client
        // see https://stackoverflow.com/a/3003233
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? "";
        filter_var($ip, FILTER_VALIDATE_IP) && $this->tracker->setIp($ip);

        $this->tracker->doTrackPageView($pageTitle);
    }

}