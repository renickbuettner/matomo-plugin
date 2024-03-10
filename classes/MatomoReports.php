<?php namespace Renick\Matomo\Classes;

use Http;
use Str;
use Cache;

class MatomoReports
{
    protected string $remote_url;
    protected string $auth_token;
    protected string $site_id;
    protected int $limit;
    protected string $period;
    protected int $cache_seconds = 60;

    public function __construct(string $url, string $token, int $siteId)
    {
        $this->auth_token = $token;
        $this->site_id = $siteId;
        $this->remote_url = Str::endsWith($url, '/') ? $url : "{$url}/";
        $this->limit = intval(env('MATOMO_REPORTS_LIMIT', 5));
        $this->period = env('MATOMO_REPORTS_PERIOD', 'month');
        $this->cache_seconds = intval(env('MATOMO_REPORTS_CACHE', 60));
    }

    public function getPeriod(bool $translate = false): string
    {
        if ($translate) {
            return trans("renick.matomo::lang.periods.{$this->period}") ?? $this->period;
        }

        return $this->period;
    }

    protected function getRemoteURL(string $method, string $period = null, string $date = 'today', $custom = ""): string
    {
        $period = $period ?? $this->period;
        $url = "{$this->remote_url}";
        $url .= "?module=API&method={$method}";
        $url .= "&idSite={$this->site_id}&period={$period}&date={$date}";
        $url .= "&format=JSON&filter_limit=$this->limit";
        $url .= $custom;
        return $url;
    }

    protected function getRemoteData(string $url): array
    {
        $response = Http::asForm()->post($url, ['token_auth' => $this->auth_token]);
        return $response->json();
    }

    public function getVisitsSummary(): array
    {
        $that = $this;
        return Cache::remember('renick_matomo_visits_sum', $this->cache_seconds, function() use (&$that) {
            $date = match ($that->period) {
                'week' => 'last7',
                'month' => 'last30',
                'year' => 'last365',
                default => 'today',
            };

            $url = $this->getRemoteURL('VisitsSummary.getVisits', 'day', $date);
            return $this->getRemoteData($url);
        });
    }

    public function getBrowsersSummary(): array
    {
        $that = $this;
        return Cache::remember('renick_matomo_browsers_sum', $this->cache_seconds, function() use (&$that) {
            $url = $this->getRemoteURL('DevicesDetection.getBrowsers');
            return $this->getRemoteData($url);
        });
    }

    public function getCampaignSummary(): array
    {
        $that = $this;
        return Cache::remember('renick_matomo_campaign_sum', $this->cache_seconds, function() use (&$that) {
            $url = $this->getRemoteURL('Referrers.getCampaigns');
            return $this->getRemoteData($url);
        });
    }

    public function getMostVisistedPages(): array
    {
        $that = $this;
        return Cache::remember('renick_matomo_pages_sum', $this->cache_seconds, function() use (&$that) {
            $url = $this->getRemoteURL('Actions.getPageUrls', null, 'today', '&flat=1');
            return $this->getRemoteData($url);
        });
    }

    public function getCountriesSummery(): string
    {
        $url = $this->getRemoteURL('UserCountry.getCountry');
        return "";
    }

}
