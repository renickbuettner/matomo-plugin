<?php namespace Renick\Matomo\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use Throwable;
use Renick\Matomo\Classes\Compat\WidgetTitle;
use Renick\Matomo\Models\Settings;

class MostVisitedPages extends ReportWidgetBase
{
    use WidgetTitle;

    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'matomo_pages_overview';

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
            'title' => $this->titleProperty('renick.matomo::lang.report_widgets.pages.label'),
        ];
    }

    protected function loadData()
    {
        $reports = Settings::instance()
            ->getReports();

        $this->vars['pages'] = $reports->getMostVisistedPages();
        $this->vars['period'] = $reports->getPeriod(true);
    }

}
