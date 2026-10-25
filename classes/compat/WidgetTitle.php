<?php namespace Renick\Matomo\Classes\Compat;

use Lang;
use Log;
use Throwable;

/**
 * Backend lang keys for the widget title field were removed in newer October versions.
 */
trait WidgetTitle
{
    protected function titleProperty(string $defaultLabelKey): array
    {
        return [
            'title' => $this->langOr('backend::lang.dashboard.widget_title_label', 'renick.matomo::lang.report_widgets.title_label'),
            'default' => $defaultLabelKey,
            'type' => 'string',
            'validationPattern' => '^.+$',
            'validationMessage' => $this->langOr('backend::lang.dashboard.widget_title_error', 'renick.matomo::lang.report_widgets.title_error'),
        ];
    }

    /** Shows a generic message; request details (url, parameters) only go to the log. */
    protected function reportLoadError(Throwable $e): void
    {
        Log::warning('[Renick.Matomo] ' . $e->getMessage());
        $this->vars['error'] = trans('renick.matomo::lang.report_widgets.load_error');
    }

    private function langOr(string $key, string $fallback): string
    {
        return Lang::has($key) ? $key : $fallback;
    }
}
