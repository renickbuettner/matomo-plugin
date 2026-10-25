<?php namespace Renick\Matomo\Tests\Unit;

use Renick\Matomo\Components\MatomoJs;
use Renick\Matomo\Components\MTagJs;

class ComponentsTest extends MatomoTestCase
{
    private function configure(array $values): void
    {
        SettingsHelper::persist($values);
    }

    public function testComponentDetailsAreTranslated(): void
    {
        foreach ([[MatomoJs::class, 'matomojs'], [MTagJs::class, 'mtagjs']] as [$class, $code]) {
            $details = (new $class())->componentDetails();

            $this->assertSame("renick.matomo::lang.components.$code.name", $details['name']);
            $this->assertNotSame($details['name'], trans($details['name']));
            $this->assertNotSame($details['description'], trans($details['description']));
        }
    }

    public function testMatomoJsExposesTheSettings(): void
    {
        $this->configure(['remote_url' => 'https://matomo.example.invalid/', 'matomo_site_id' => 7, 'use_cookies' => 1]);
        $component = new MatomoJs();

        $this->assertSame('https://matomo.example.invalid/', $component->remoteUrl());
        $this->assertSame('7', $component->matomoSiteId());
        $this->assertTrue($component->useCookies());
    }

    public function testMatomoJsFallsBackToEmptyValues(): void
    {
        $component = new MatomoJs();

        $this->assertSame('', $component->remoteUrl());
        $this->assertSame('', $component->matomoSiteId());
        $this->assertFalse($component->useCookies());
    }

    public function testMTagJsExposesTheSettings(): void
    {
        $this->configure(['remote_url' => 'https://matomo.example.invalid/', 'container_id' => 'ab12cd34']);
        $component = new MTagJs();

        $this->assertSame('https://matomo.example.invalid/', $component->remoteUrl());
        $this->assertSame('ab12cd34', $component->containerId());
    }

    public function testMTagJsFallsBackToEmptyValues(): void
    {
        $component = new MTagJs();

        $this->assertSame('', $component->remoteUrl());
        $this->assertSame('', $component->containerId());
    }

    public function testComponentsReportAdminStateFromTheSettingsModel(): void
    {
        $this->assertFalse((new MatomoJs())->isLoggedInAdmin());
        $this->assertFalse((new MTagJs())->isLoggedInAdmin());

        $user = \Backend\Models\User::create([
            'login' => 'tester', 'email' => 'tester@example.invalid', 'first_name' => 'T', 'last_name' => 'T',
            'password' => 'Secret-123!', 'password_confirmation' => 'Secret-123!',
        ]);
        \BackendAuth::login($user);

        $this->assertTrue((new MatomoJs())->isLoggedInAdmin());
        $this->assertTrue((new MTagJs())->isLoggedInAdmin());
    }

    /** @dataProvider partials */
    #[\PHPUnit\Framework\Attributes\DataProvider('partials')]
    public function testPartialsHideTheScriptForAdmins(string $partial, string $marker, string $adminMarker): void
    {
        $source = file_get_contents(__DIR__ . "/../../components/$partial/default.htm");

        $this->assertStringContainsString('{% if __SELF__.isLoggedInAdmin() %}', $source);
        $this->assertStringContainsString($adminMarker, $source);
        $this->assertStringContainsString($marker, $source);
        $this->assertLessThan(strpos($source, $marker), strpos($source, '{% else %}'), 'tracking code only in the else branch');
    }

    public static function partials(): array
    {
        return [
            'matomojs' => ['matomojs', "_paq.push(['trackPageView'])", "const _paq = 'disabled for admin user'"],
            'mtagjs' => ['mtagjs', 'container_', "const _mtm = 'disabled for admin user'"],
        ];
    }

    /** @dataProvider partials */
    #[\PHPUnit\Framework\Attributes\DataProvider('partials')]
    public function testPartialsRenderWithTwig(string $partial, string ...$unused): void
    {
        $this->configure(['remote_url' => 'https://matomo.example.invalid/', 'matomo_site_id' => 7, 'container_id' => 'ab12cd34']);
        $component = $partial === 'matomojs' ? new MatomoJs() : new MTagJs();
        $twig = new \Twig\Environment(new \Twig\Loader\ArrayLoader([
            'p' => file_get_contents(__DIR__ . "/../../components/$partial/default.htm"),
        ]));
        $twig->addGlobal('__SELF__', $component);

        $html = $twig->render('p');

        $this->assertStringContainsString('https://matomo.example.invalid/', $html);
        $this->assertStringContainsString($partial === 'matomojs' ? "'setSiteId', '7'" : 'container_ab12cd34.js', $html);
        $this->assertStringNotContainsString('disabled for admin user', $html);
    }
}
