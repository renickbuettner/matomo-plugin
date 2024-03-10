<?php namespace Renick\Matomo\Classes;

use Str;

class MatomoReports
{
    protected string $remote_url;
    protected string $auth_token;
    protected string $site_id;
    protected int $limit;
    protected string $period;

    public function __construct(string $url, string $token, int $siteId)
    {
        $this->auth_token = $token;
        $this->site_id = $siteId;
        $this->remote_url = Str::endsWith('/', $url) ? $url : "{$url}/";
        $this->limit = intval(env('MATOMO_REPORTS_LIMIT', 5));
        $this->period = env('MATOMO_REPORTS_PERIOD', 'month');
    }

    protected function getRemoteURL(string $method): string
    {
        $url = "{$this->remote_url}";
        $url .= "?module=API&method={$method}";
        $url .= "&idSite={$this->site_id}&period={$this->period}&date=today";
        $url .= "&format=JSON&filter_limit=$this->limit";
        $url .= "&token_auth={$this->auth_token}";
        return $url;
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

    public function getCountriesSummery(): string
    {
        $url = $this->getRemoteURL('UserCountry.getCountry');
        return "";
    }

}
