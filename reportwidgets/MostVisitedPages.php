<?php namespace Renick\Matomo\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use Exception;
use Renick\Matomo\Models\Settings;

class MostVisitedPages extends ReportWidgetBase
{
    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'matomo_most_visited_pages';

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
                'default' => 'renick.matomo::lang.report_widgets.pages.label',
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

        $this->vars['pages'] = $reports->getMostVisistedPages();
        $this->vars['period'] = $reports->getPeriod(true);
    }

}
