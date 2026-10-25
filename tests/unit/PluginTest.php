<?php namespace Renick\Matomo\Tests\Unit;

use Renick\Matomo\Models\Settings;
use Renick\Matomo\Plugin;
use Renick\Matomo\ReportWidgets\BrowserOverview;
use Renick\Matomo\ReportWidgets\CampaignOverview;
use Renick\Matomo\ReportWidgets\MostVisitedPages;
use Renick\Matomo\ReportWidgets\TrafficOverview;
use System\Classes\PluginManager;

class PluginTest extends MatomoTestCase
{
    protected function plugin(): Plugin
    {
        return PluginManager::instance()->findByIdentifier('Renick.Matomo');
    }

    public function testPluginIsRegistered(): void
    {
        $this->assertInstanceOf(Plugin::class, $this->plugin());
        $this->assertSame('renick.matomo::lang.plugin.name', $this->plugin()->pluginDetails()['name']);
    }

    public function testSettingsRegistration(): void
    {
        $item = $this->plugin()->registerSettings()['settings'];

        $this->assertSame(Settings::class, $item['class']);
        $this->assertSame(['renick.matomo.permissions.manage_settings'], $item['permissions']);
        $this->assertSame('renick.matomo::lang.backend.settings.label', $item['label']);
        $this->assertSame('renick.matomo::lang.backend.settings.description', $item['description']);
        $this->assertStringContainsString('matomo', $item['keywords']);
    }

    public function testPermissionsAreRegisteredForDevelopers(): void
    {
        $permissions = $this->plugin()->registerPermissions();

        $this->assertEqualsCanonicalizing(
            ['renick.matomo.permissions.report_widgets', 'renick.matomo.permissions.manage_settings'],
            array_keys($permissions)
        );
        foreach ($permissions as $code => $permission) {
            $this->assertSame('renick.matomo::lang.permissions.tab', $permission['tab'], $code);
            $this->assertSame(['developer'], $permission['roles'], $code);
            $this->assertNotSame($permission['label'], trans($permission['label']), "label of $code is translated");
        }
    }

    public function testPermissionsAreKnownToTheBackendRoleManager(): void
    {
        $codes = collect(\Backend\Classes\RoleManager::instance()->listPermissions())->pluck('code')->all();

        $this->assertContains('renick.matomo.permissions.manage_settings', $codes);
        $this->assertContains('renick.matomo.permissions.report_widgets', $codes);
    }

    public function testComponentsAreRegistered(): void
    {
        $this->assertSame([
            'Renick\Matomo\Components\MatomoJs' => 'matomojs',
            'Renick\Matomo\Components\MTagJs' => 'mtagjs',
        ], $this->plugin()->registerComponents());
    }

    public function testPluginHasNoOwnNavigation(): void
    {
        $this->assertSame([], $this->plugin()->registerNavigation());
    }

    public function testReportWidgetsAreNotRegisteredWithoutConfiguration(): void
    {
        $this->assertSame([], $this->plugin()->registerReportWidgets());
    }

    public function testReportWidgetsAreNotRegisteredWithoutAuthToken(): void
    {
        SettingsHelper::set(['remote_url' => 'https://matomo.example.invalid/', 'matomo_site_id' => 1]);

        $this->assertSame([], $this->plugin()->registerReportWidgets());
    }

    public function testReportWidgetsAreRegisteredWithCompleteConfiguration(): void
    {
        SettingsHelper::set(['remote_url' => 'https://matomo.example.invalid/', 'matomo_site_id' => 1, 'auth_token' => 'abc']);

        $widgets = $this->plugin()->registerReportWidgets();

        $this->assertEqualsCanonicalizing(
            [TrafficOverview::class, BrowserOverview::class, CampaignOverview::class, MostVisitedPages::class],
            array_keys($widgets)
        );
        foreach ($widgets as $class => $widget) {
            $this->assertSame('dashboard', $widget['context'], $class);
            $this->assertSame(['renick.matomo.permissions.report_widgets'], $widget['permissions'], $class);
            $this->assertNotSame($widget['label'], trans($widget['label']), "label of $class is translated");
        }
    }

    public function testMiddlewareIsNotPushedWhenRunningUnitTests(): void
    {
        SettingsHelper::set(['remote_url' => 'https://matomo.example.invalid/', 'matomo_site_id' => 1, 'use_middleware' => 1]);

        $kernel = $this->app[\Illuminate\Contracts\Http\Kernel::class];
        $this->plugin()->boot();

        $this->assertFalse($kernel->hasMiddleware(\Renick\Matomo\Classes\MatomoMiddleware::class));
    }

    public function testLanguageFilesHaveTheSameKeys(): void
    {
        $en = \Illuminate\Support\Arr::dot(require __DIR__ . '/../../lang/en/lang.php');
        $de = \Illuminate\Support\Arr::dot(require __DIR__ . '/../../lang/de/lang.php');

        $this->assertSame([], array_values(array_diff(array_keys($en), array_keys($de))), 'keys missing in de');
        $this->assertSame([], array_values(array_diff(array_keys($de), array_keys($en))), 'keys missing in en');
    }

    public function testAllLanguageKeysUsedByThePluginExist(): void
    {
        $root = __DIR__ . '/../..';
        $files = array_merge(
            glob("$root/*.php"), glob("$root/*/*.php"), glob("$root/*/*/*.php"), glob("$root/*/*/*.htm"),
            glob("$root/*/*/*/*.htm"), glob("$root/models/settings/*.yaml")
        );
        $missing = [];
        foreach ($files as $file) {
            if (str_contains($file, '/tests/') || str_contains($file, '/vendor/')) {
                continue;
            }
            preg_match_all('/renick\.matomo::(lang\.[A-Za-z0-9_.]+)/', file_get_contents($file), $m);
            foreach ($m[0] as $key) {
                foreach (['en', 'de'] as $locale) {
                    if (!\Lang::has($key, $locale)) {
                        $missing[] = "$key ($locale)";
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)));
    }

    public function testVersionHistoryReferencesExistingUpdateScripts(): void
    {
        $versions = \Symfony\Component\Yaml\Yaml::parseFile(__DIR__ . '/../../updates/version.yaml');
        $this->assertNotEmpty($versions);
        foreach ($versions as $version => $lines) {
            $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', (string) $version);
            foreach ((array) $lines as $line) {
                if (str_ends_with($line, '.php')) {
                    $this->assertFileExists(__DIR__ . '/../../updates/' . $line);
                }
            }
        }
    }
}
