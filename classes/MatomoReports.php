<?php namespace Renick\Matomo\Classes;

use MatomoTracker;
use VisualAppeal\Matomo;

class MatomoReports
{
    private $api;

    public function __construct(string $url, string $token, int $siteId)
    {
        $this->api = new Matomo($url, $token, strval($siteId));
    }

    public function getVisitsSummary(): string
    {
        try {
            $this->api->setPeriod(Matomo::PERIOD_DAY);
            $this->api->setDate('previous30');

            $img = base64_encode(
                $this->api->getImageGraph(
                    "VisitsSummary",
                    "get",
                    Matomo::GRAPH_EVOLUTION
                )
            );
            return "data:image/png;base64,{$img}";
        } catch (\Exception $e) {
            return "";
        }
    }

    public function getBrowsersSummary(): string
    {
        try {
            $this->api->setPeriod(Matomo::PERIOD_MONTH);
            $this->api->setDate(Matomo::DATE_TODAY);

            $img = base64_encode(
                $this->api->getImageGraph(
                    "DevicesDetection",
                    "getBrowsers",
                    "horizontalBar"
                )
            );
            return "data:image/png;base64,{$img}";
        } catch (\Exception $e) {
            return "";
        }
    }

    public function getScreenSizeSummary(): string
    {
        try {
            $this->api->setPeriod(Matomo::PERIOD_MONTH);
            $this->api->setDate(Matomo::DATE_TODAY);

            $img = base64_encode(
                $this->api->getImageGraph(
                    "Resolution",
                    "getResolution",
                    "horizontalBar"
                )
            );
            return "data:image/png;base64,{$img}";
        } catch (\Exception $e) {
            return "";
        }
    }

}