<?php namespace Renick\Matomo\Classes;

class MatomoReports
{
    protected string $remote_url;
    protected string $auth_token;
    protected string $site_id;

    public function __construct(string $url, string $token, int $siteId)
    {
        $this->remote_url = $url;
        $this->auth_token = $token;
        $this->site_id = $siteId;
    }

    public function getVisitsSummary(): string
    {
        return "";
    }

    public function getBrowsersSummary(): string
    {
        return "";
    }

    public function getScreenSizeSummary(): string
    {
        return "";
    }

}
