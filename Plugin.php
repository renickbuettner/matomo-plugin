<?php namespace Renick\Matomo;

use App;
use Illuminate\Contracts\Http\Kernel;
use Renick\Matomo\Classes\MatomoMiddleware;
use Renick\Matomo\Models\Settings;
use Renick\Matomo\ReportWidgets\BrowserOverview;
use Renick\Matomo\ReportWidgets\CampaignOverview;
use Renick\Matomo\ReportWidgets\MostVisitedPages;
use Renick\Matomo\ReportWidgets\TrafficOverview;
use System\Classes\PluginBase;
use System\Classes\SettingsManager;

class Plugin extends PluginBase
{
    public function boot(): void
    {
        // ToDo: cache the settings within memory (at least support for this ;-))
        $settings = Settings::instance();

        if (App::runningInConsole()
            || App::runningUnitTests()
            || App::runningInBackend()
            || !$settings->valid
            || !$settings->use_middleware) {
            return;
        }

        // web group has the session started, so logged-in admins can be skipped; global middleware runs before it
        $router = $this->app['router'];
        if (method_exists($router, 'pushMiddlewareToGroup') && isset($router->getMiddlewareGroups()['web'])) {
            $router->pushMiddlewareToGroup('web', MatomoMiddleware::class);
            return;
        }

        $this->app[Kernel::class]->pushMiddleware(MatomoMiddleware::class);
    }

    public function registerSettings(): array
    {
        return [
            'settings' => [
                'label' => 'renick.matomo::lang.backend.settings.label',
                'description' => 'renick.matomo::lang.backend.settings.description',
                'category' => SettingsManager::CATEGORY_CMS,
                'icon' => 'icon-area-chart',
                'order' => 500,
                'keywords' => 'matomo piwik analytics',
                'class' => Settings::class,
                'permissions' => ['renick.matomo.permissions.manage_settings'],
            ]
        ];
    }

    public function registerPermissions()
    {
        return [
            'renick.matomo.permissions.report_widgets' => [
                'tab'   => 'renick.matomo::lang.permissions.tab',
                'label' => 'renick.matomo::lang.permissions.report_widgets.label',
                'roles' => ['developer'],
            ],
            'renick.matomo.permissions.manage_settings' => [
                'tab'   => 'renick.matomo::lang.permissions.tab',
                'label' => 'renick.matomo::lang.permissions.manage_settings.label',
                'roles' => ['developer'],
            ],
        ];
    }

    public function registerComponents()
    {
        return [
            'Renick\Matomo\Components\MatomoJs' => 'matomojs',
            'Renick\Matomo\Components\MTagJs' => 'mtagjs',
        ];
    }

    public function registerReportWidgets()
    {
        if (!Settings::instance()->report_widgets_enabled) {
            return [];
        }

        return [
            TrafficOverview::class => [
                'label'       => 'renick.matomo::lang.report_widgets.traffic.label',
                'context'     => 'dashboard',
                'permissions' => ['renick.matomo.permissions.report_widgets']
            ],
            BrowserOverview::class => [
                'label'       => 'renick.matomo::lang.report_widgets.browser.label',
                'context'     => 'dashboard',
                'permissions' => ['renick.matomo.permissions.report_widgets']
            ],
            CampaignOverview::class => [
                'label'       => 'renick.matomo::lang.report_widgets.campaign.label',
                'context'     => 'dashboard',
                'permissions' => ['renick.matomo.permissions.report_widgets']
            ],
            MostVisitedPages::class => [
                'label'       => 'renick.matomo::lang.report_widgets.pages.label',
                'context'     => 'dashboard',
                'permissions' => ['renick.matomo.permissions.report_widgets']
            ],
        ];
    }
}
