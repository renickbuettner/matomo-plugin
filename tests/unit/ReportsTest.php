<?php namespace Renick\Matomo\Tests\Unit;

use Cache;
use Http;
use Illuminate\Http\Client\Request;
use Renick\Matomo\Classes\MatomoReports;

class ReportsTest extends MatomoTestCase
{
    private const URL = 'https://matomo.example.invalid/';

    public function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function tearDown(): void
    {
        foreach (['MATOMO_REPORTS_LIMIT', 'MATOMO_REPORTS_PERIOD', 'MATOMO_REPORTS_CACHE'] as $name) {
            $this->setEnv($name, null);
        }
        parent::tearDown();
    }

    private function setEnv(string $name, ?string $value): void
    {
        $defaults = ['MATOMO_REPORTS_LIMIT' => '5', 'MATOMO_REPORTS_PERIOD' => 'month', 'MATOMO_REPORTS_CACHE' => '60'];
        $value ??= $defaults[$name];
        putenv("$name=$value");
        $_ENV[$name] = $_SERVER[$name] = $value;
    }

    private function reports(string $url = self::URL): MatomoReports
    {
        return new MatomoReports($url, 'dummy-token', 7);
    }

    private function remoteUrl(MatomoReports $reports, string $method, ...$args): string
    {
        return self::callProtectedMethod($reports, 'getRemoteURL', array_merge([$method], $args));
    }

    public function testUrlGetsTrailingSlash(): void
    {
        $this->assertSame(self::URL, $this->getProtectedProperty($this->reports('https://matomo.example.invalid'), 'remote_url'));
        $this->assertSame(self::URL, $this->getProtectedProperty($this->reports(self::URL), 'remote_url'));
        $this->assertSame('https://example.invalid/matomo/', $this->getProtectedProperty($this->reports('https://example.invalid/matomo'), 'remote_url'));
    }

    public function testDefaultsFromEnvironment(): void
    {
        $reports = $this->reports();

        $this->assertSame('month', $reports->getPeriod());
        $this->assertSame(5, $this->getProtectedProperty($reports, 'limit'));
        $this->assertSame(60, $this->getProtectedProperty($reports, 'cache_seconds'));
    }

    public function testEnvironmentOverrides(): void
    {
        $this->setEnv('MATOMO_REPORTS_LIMIT', '12');
        $this->setEnv('MATOMO_REPORTS_PERIOD', 'week');
        $this->setEnv('MATOMO_REPORTS_CACHE', '5');

        $reports = $this->reports();

        $this->assertSame('week', $reports->getPeriod());
        $this->assertSame(12, $this->getProtectedProperty($reports, 'limit'));
        $this->assertSame(5, $this->getProtectedProperty($reports, 'cache_seconds'));
    }

    public function testPeriodLabelIsTranslated(): void
    {
        $this->assertSame('Month', $this->reports()->getPeriod(true));

        \App::setLocale('de');
        $this->assertSame('Monat', $this->reports()->getPeriod(true));
    }

    public function testRemoteUrlIsBuiltFromSettings(): void
    {
        $url = $this->remoteUrl($this->reports(), 'Actions.getPageUrls');

        $this->assertSame(
            self::URL . '?module=API&method=Actions.getPageUrls&idSite=7&period=month&date=today&format=JSON&filter_limit=5',
            $url
        );
    }

    public function testRemoteUrlAcceptsPeriodDateAndCustomParameters(): void
    {
        $url = $this->remoteUrl($this->reports(), 'X.y', 'day', 'last30', '&flat=1');

        $this->assertStringContainsString('&period=day&date=last30&', $url);
        $this->assertStringEndsWith('&flat=1', $url);
    }

    /** @dataProvider periods */
    #[\PHPUnit\Framework\Attributes\DataProvider('periods')]
    public function testVisitsSummaryDateRangeDependsOnPeriod(string $period, string $expectedDate): void
    {
        $this->setEnv('MATOMO_REPORTS_PERIOD', $period);
        Http::fake(['*' => Http::response(['2026-10-01' => 3])]);

        $this->assertSame(['2026-10-01' => 3], $this->reports()->getVisitsSummary());

        Http::assertSent(function (Request $request) use ($expectedDate) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $q);
            return $q['method'] === 'VisitsSummary.getVisits' && $q['period'] === 'day' && $q['date'] === $expectedDate;
        });
    }

    public static function periods(): array
    {
        return [['week', 'last7'], ['month', 'last30'], ['year', 'last365'], ['day', 'today'], ['unknown', 'today']];
    }

    public function testTokenIsSentInTheBodyNotInTheUrl(): void
    {
        Http::fake(['*' => Http::response([])]);

        $this->reports()->getBrowsersSummary();

        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && !str_contains($request->url(), 'dummy-token')
                && !str_contains($request->url(), 'token_auth')
                && $request->body() === 'token_auth=dummy-token'
                && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded');
        });
    }

    /** @dataProvider reportMethods */
    #[\PHPUnit\Framework\Attributes\DataProvider('reportMethods')]
    public function testEachReportCallsItsMatomoMethod(string $php, string $apiMethod, string $extra): void
    {
        $rows = [['label' => 'row', 'nb_visits' => 4]];
        Http::fake(['*' => Http::response($rows)]);

        $this->assertSame($rows, $this->reports()->$php());

        Http::assertSent(function (Request $request) use ($apiMethod, $extra) {
            return str_contains($request->url(), "method={$apiMethod}&idSite=7") && str_ends_with($request->url(), $extra);
        });
    }

    public static function reportMethods(): array
    {
        return [
            ['getBrowsersSummary', 'DevicesDetection.getBrowsers', 'filter_limit=5'],
            ['getCampaignSummary', 'Referrers.getCampaigns', 'filter_limit=5'],
            ['getMostVisistedPages', 'Actions.getPageUrls', '&flat=1'],
        ];
    }

    public function testResultsAreCachedForTheConfiguredTime(): void
    {
        Http::fake(['*' => Http::sequence()->push([['label' => 'first']])->push([['label' => 'second']])]);
        $reports = $this->reports();

        $this->assertSame([['label' => 'first']], $reports->getBrowsersSummary());
        $this->assertSame([['label' => 'first']], $reports->getBrowsersSummary(), 'served from cache');
        Http::assertSentCount(1);
    }

    public function testEachReportHasItsOwnCacheKey(): void
    {
        Http::fake(['*' => Http::response([['label' => 'x']])]);
        $reports = $this->reports();

        $reports->getVisitsSummary();
        $reports->getBrowsersSummary();
        $reports->getCampaignSummary();
        $reports->getMostVisistedPages();

        Http::assertSentCount(4);
        foreach (['visits', 'browsers', 'campaign', 'pages'] as $key) {
            $this->assertTrue(Cache::has("renick_matomo_{$key}_sum"), $key);
        }
    }

    public function testConnectionErrorsAreThrownAsExceptions(): void
    {
        Http::fake(['*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('unreachable')]);

        $this->expectException(\Exception::class);
        $this->reports()->getBrowsersSummary();
    }

    public function testNonJsonAnswerFromMatomo(): void
    {
        Http::fake(['*' => Http::response('<html>Login</html>', 200, ['Content-Type' => 'text/html'])]);

        $this->expectException(\RuntimeException::class);
        $this->reports()->getBrowsersSummary();
    }

    public function testCountriesSummary(): void
    {
        Http::fake(['*' => Http::response([['label' => 'Germany', 'nb_visits' => 3]], 200)]);
        $this->assertSame('Germany', $this->reports()->getCountriesSummary()[0]['label']);
    }

    public function testMatomoErrorPayloadThrows(): void
    {
        Http::fake(['*' => Http::response(['result' => 'error', 'message' => 'bad token'], 200)]);
        $this->expectExceptionMessage('bad token');
        $this->reports()->getCampaignSummary();
    }
}
