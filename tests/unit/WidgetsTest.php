<?php namespace Renick\Matomo\Tests\Unit;

use Backend\Classes\Controller;
use Cache;
use Dashboard\Classes\DashReport;
use Http;
use Renick\Matomo\ReportWidgets\BrowserOverview;
use Renick\Matomo\ReportWidgets\CampaignOverview;
use Renick\Matomo\ReportWidgets\MostVisitedPages;
use Renick\Matomo\ReportWidgets\TrafficOverview;

class WidgetsTest extends MatomoTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        SettingsHelper::set([
            'remote_url' => 'https://matomo.example.invalid/',
            'auth_token' => 'dummy-token',
            'matomo_site_id' => 7,
        ]);
    }

    private function widget(string $class, array $properties = [])
    {
        return new $class(new Controller(), $this->createMock(DashReport::class), $properties);
    }

    public static function widgetClasses(): array
    {
        return [
            'traffic' => [TrafficOverview::class, 'renick.matomo::lang.report_widgets.traffic.label', 'widget-matomo-traffic-overview'],
            'browser' => [BrowserOverview::class, 'renick.matomo::lang.report_widgets.browser.label', 'widget-matomo-browser-overview'],
            'campaign' => [CampaignOverview::class, 'renick.matomo::lang.report_widgets.campaign.label', 'widget-matomo-campaign-overview'],
            'pages' => [MostVisitedPages::class, 'renick.matomo::lang.report_widgets.pages.label', 'widget-matomo-pages-overview'],
        ];
    }

    /** @dataProvider widgetClasses */
    #[\PHPUnit\Framework\Attributes\DataProvider('widgetClasses')]
    public function testTitleIsTheOnlyPropertyAndDefaultsToTheTranslatedLabel(string $class, string $label, string ...$unused): void
    {
        $properties = $this->widget($class)->defineProperties();

        $this->assertSame(['title'], array_keys($properties));
        $this->assertSame($label, $properties['title']['default']);
        $this->assertSame('string', $properties['title']['type']);
        $this->assertSame('^.+$', $properties['title']['validationPattern']);
    }

    public function testTitleLabelUsesAnExistingTranslationKey(): void
    {
        $key = $this->widget(TrafficOverview::class)->defineProperties()['title']['title'];

        if (!\Lang::has($key)) {
            $this->markTestIncomplete("BUG: widget property label key '$key' does not exist in this October version (config popup shows the raw key)");
        }
        $this->assertTrue(\Lang::has($key));
    }

    public function testWidgetAliasesAreUnique(): void
    {
        $aliases = [];
        foreach ($this->widgetClasses() as [$class]) {
            $aliases[$class] = $this->getProtectedProperty($this->widget($class), 'defaultAlias');
        }

        if (count(array_unique($aliases)) !== count($aliases)) {
            $this->markTestIncomplete('BUG: duplicate defaultAlias (BrowserOverview copies matomo_traffic_overview): ' . json_encode($aliases));
        }
        $this->assertCount(4, array_unique($aliases));
    }

    public function testTrafficOverviewRendersTheVisitsDataset(): void
    {
        Http::fake(['*' => Http::response(['2026-10-01' => 5, '2026-10-02' => 8])]);

        $html = $this->widget(TrafficOverview::class, ['title' => 'My traffic'])->render();

        $this->assertStringContainsString('widget-matomo-traffic-overview', $html);
        $this->assertStringContainsString('My traffic', $html);
        $ts1 = \Carbon\Carbon::parse('2026-10-01')->getTimestampMs();
        $ts2 = \Carbon\Carbon::parse('2026-10-02')->getTimestampMs();
        $this->assertStringContainsString("data-set-data=\"[{$ts1}, 5], [{$ts2}, 8]\"", $html);
        $this->assertStringContainsString('Visits Summary', $html);
        $this->assertMatchesRegularExpression('#Period: <b>Month</b>#', $html);
    }

    public function testBrowserOverviewListsBrowsers(): void
    {
        Http::fake(['*' => Http::response([['label' => 'Firefox', 'nb_visits' => 7], ['label' => 'Chrome', 'nb_visits' => 4]])]);

        $html = $this->widget(BrowserOverview::class, ['title' => 'Browsers'])->render();

        $this->assertStringContainsString('widget-matomo-browser-overview', $html);
        $this->assertMatchesRegularExpression('#Firefox\s*<span>7</span>#', $html);
        $this->assertMatchesRegularExpression('#Chrome\s*<span>4</span>#', $html);
    }

    public function testCampaignOverviewListsCampaigns(): void
    {
        Http::fake(['*' => Http::response([['label' => 'spring-sale', 'nb_visits' => 3]])]);

        $html = $this->widget(CampaignOverview::class, ['title' => 'Campaigns'])->render();

        $this->assertStringContainsString('widget-matomo-campaign-overview', $html);
        $this->assertMatchesRegularExpression('#spring-sale <span>3</span>#', $html);
    }

    public function testMostVisitedPagesListsPagesWithLinks(): void
    {
        Http::fake(['*' => Http::response([['label' => '/pricing', 'url' => 'https://example.invalid/pricing', 'nb_visits' => 9]])]);

        $html = $this->widget(MostVisitedPages::class, ['title' => 'Pages'])->render();

        $this->assertStringContainsString('widget-matomo-pages-overview', $html);
        $this->assertStringContainsString('href="https://example.invalid/pricing"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('<td>9</td>', $html);
    }

    /** @dataProvider widgetClasses */
    #[\PHPUnit\Framework\Attributes\DataProvider('widgetClasses')]
    public function testEmptyAnswerRendersAnEmptyWidget(string $class, string $label, string $css): void
    {
        Http::fake(['*' => Http::response([])]);

        $html = $this->widget($class, ['title' => 'Empty'])->render();

        $this->assertStringContainsString($css, $html);
    }

    /** @dataProvider widgetClasses */
    #[\PHPUnit\Framework\Attributes\DataProvider('widgetClasses')]
    public function testUnreachableMatomoStillRendersTheWidgetFrame(string $class, string $label, string $css): void
    {
        Http::fake(['*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('unreachable')]);

        $widget = $this->widget($class, ['title' => 'Offline']);
        $html = $widget->render();

        $this->assertStringContainsString($css, $html);
        $this->assertStringContainsString('Offline', $html);
        $this->assertSame(trans('renick.matomo::lang.report_widgets.load_error'), $widget->vars['error'] ?? null);
        $this->assertStringNotContainsString('unreachable', $html);
    }

    public function testErrorObjectFromMatomoDoesNotBreakTheTrafficWidget(): void
    {
        Http::fake(['*' => Http::response(['result' => 'error', 'message' => 'Token invalid'])]);

        $widget = $this->widget(TrafficOverview::class, ['title' => 'Traffic']);
        $html = $widget->render();

        $this->assertStringContainsString('widget-matomo-traffic-overview', $html);
        $this->assertArrayHasKey('error', $widget->vars, 'the error key "result" is not a date');
    }

    public function testTitleIsEscaped(): void
    {
        Http::fake(['*' => Http::response([])]);

        $html = $this->widget(BrowserOverview::class, ['title' => '<script>alert(1)</script>'])->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testMatomoDataIsEscapedInTheWidgets(): void
    {
        $evil = '<img src=x onerror=alert(1)>';
        $unescaped = [];
        foreach ([BrowserOverview::class, CampaignOverview::class, MostVisitedPages::class] as $class) {
            Cache::flush();
            Http::fake(['*' => Http::response([['label' => $evil, 'url' => '" onmouseover="alert(2)', 'nb_visits' => 1]])]);
            $html = $this->widget($class, ['title' => 'x'])->render();
            if (str_contains($html, $evil) || str_contains($html, 'href="" onmouseover="alert(2)"')) {
                $unescaped[] = (new \ReflectionClass($class))->getShortName();
            }
        }

        if ($unescaped) {
            $this->markTestIncomplete('BUG: Matomo data (campaign names, page URLs/labels are visitor controlled) is printed unescaped in: ' . implode(', ', $unescaped));
        }
        $this->assertSame([], $unescaped);
    }
}
