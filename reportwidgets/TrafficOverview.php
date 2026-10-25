<?php namespace Renick\Matomo\ReportWidgets;

use Arr;
use Backend\Classes\ReportWidgetBase;
use Carbon\Carbon;
use Throwable;
use Renick\Matomo\Classes\Compat\WidgetTitle;
use Renick\Matomo\Models\Settings;

class TrafficOverview extends ReportWidgetBase
{
    use WidgetTitle;

    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'matomo_traffic_overview';

    public function render()
    {
        try {
            $this->loadData();
        }
        catch (Throwable $ex) {
            $this->reportLoadError($ex);
        }


        return $this->makePartial('widget');
    }

    public function defineProperties()
    {
        return [
            'title' => $this->titleProperty('renick.matomo::lang.report_widgets.traffic.label'),
        ];
    }

    protected function loadData()
    {
        $reports = Settings::instance()
            ->getReports();

        $visits = $reports->getVisitsSummary();
        $dataset = collect($visits ?? [])->map(function($value, $key) {
            $time = Carbon::parse($key)->getTimestampMs();
            return "[{$time}, {$value}]";
        })->join(', ');

        $this->vars['dataset'] = $dataset;
        $this->vars['period'] = $reports->getPeriod(true);
    }

}
