<?php namespace Renick\Matomo\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use Exception;
use Renick\Matomo\Models\Settings;

class TrafficOverview extends ReportWidgetBase
{
    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'matomo_traffic_overview';

    public function render()
    {
        try {
            $this->loadData();
        }
        catch (Exception $ex) {
            $this->vars['error'] = $ex->getMessage();
        }


        return $this->makePartial('widget');
    }

    public function defineProperties()
    {
        return [
            'title' => [
                'title' => 'backend::lang.dashboard.widget_title_label',
                'default' => 'renick.matomo::lang.report_widgets.traffic.label',
                'type' => 'string',
                'validationPattern' => '^.+$',
                'validationMessage' => 'backend::lang.dashboard.widget_title_error'
            ],
        ];
    }

    protected function loadData()
    {
        $reports = Settings::instance()
            ->getReports();

        $this->vars['visits'] = $reports->getVisitsSummary();
    }

}