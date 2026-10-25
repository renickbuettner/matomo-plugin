<?php namespace Renick\Matomo\Classes\Compat;

use Lang;

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

    private function langOr(string $key, string $fallback): string
    {
        return Lang::has($key) ? $key : $fallback;
    }
}
