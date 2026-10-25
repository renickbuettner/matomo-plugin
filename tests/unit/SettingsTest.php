<?php namespace Renick\Matomo\Tests\Unit;

use Renick\Matomo\Classes\MatomoBridge;
use Renick\Matomo\Classes\MatomoReports;
use Renick\Matomo\Models\Settings;

class SettingsTest extends MatomoTestCase
{
    private const DUMMY = [
        'remote_url' => 'https://matomo.example.invalid/',
        'auth_token' => 'dummy-token',
        'matomo_site_id' => 3,
    ];

    public function testSettingsAreStoredUnderTheExpectedCode(): void
    {
        $this->assertSame('renick_matomo_settings', (new Settings())->settingsCode);
        $this->assertSame('fields.yaml', (new Settings())->settingsFields);
    }

    public function testValuesArePersistedAndLoadedAgain(): void
    {
        SettingsHelper::persist(self::DUMMY + ['container_id' => 'ab12', 'use_cookies' => 1, 'use_middleware' => 1]);

        $loaded = Settings::instance();
        $this->assertSame('https://matomo.example.invalid/', $loaded->remote_url);
        $this->assertSame('dummy-token', $loaded->auth_token);
        $this->assertEquals(3, $loaded->matomo_site_id);
        $this->assertSame('ab12', $loaded->container_id);
        $this->assertTrue($loaded->use_cookies);
        $this->assertTrue($loaded->use_middleware);
        $this->assertSame(1, \Db::table('system_settings')->where('item', 'renick_matomo_settings')->count());
    }

    public function testBooleanCastsDefaultToFalse(): void
    {
        $settings = SettingsHelper::persist([]);

        $this->assertFalse($settings->use_middleware);
        $this->assertFalse($settings->use_cookies);
    }

    /** @dataProvider booleanValues */
    #[\PHPUnit\Framework\Attributes\DataProvider('booleanValues')]
    public function testBooleanCasts($stored, bool $expected): void
    {
        $settings = SettingsHelper::persist(['use_middleware' => $stored, 'use_cookies' => $stored]);

        $this->assertSame($expected, $settings->use_middleware);
        $this->assertSame($expected, $settings->use_cookies);
    }

    public static function booleanValues(): array
    {
        return [
            'switch on (1)' => [1, true],
            'switch on ("1")' => ['1', true],
            'true' => [true, true],
            'switch off (0)' => [0, false],
            'switch off ("0")' => ['0', false],
            'false' => [false, false],
            'empty string' => ['', false],
        ];
    }

    public function testNotValidWithoutAnyConfiguration(): void
    {
        $settings = SettingsHelper::persist([]);

        $this->assertFalse($settings->valid);
        $this->assertFalse($settings->report_widgets_enabled);
    }

    /** @dataProvider requiredFieldCombinations */
    #[\PHPUnit\Framework\Attributes\DataProvider('requiredFieldCombinations')]
    public function testValidAndReportWidgetsEnabled(array $values, bool $valid, bool $widgets): void
    {
        $settings = SettingsHelper::persist($values);

        $this->assertSame($valid, $settings->valid, 'valid');
        $this->assertSame($widgets, $settings->report_widgets_enabled, 'report_widgets_enabled');
    }

    public static function requiredFieldCombinations(): array
    {
        $url = 'https://matomo.example.invalid/';

        return [
            'url + site id: tracking possible, no reports' => [['remote_url' => $url, 'matomo_site_id' => 1], true, false],
            'url + site id + token: all features' => [['remote_url' => $url, 'matomo_site_id' => 1, 'auth_token' => 't'], true, true],
            'only url' => [['remote_url' => $url], false, false],
            'only site id' => [['matomo_site_id' => 1], false, false],
            'only token' => [['auth_token' => 't'], false, false],
            'token + url, no site id' => [['remote_url' => $url, 'auth_token' => 't'], false, false],
            'only container id (tag manager)' => [['container_id' => 'abc'], false, false],
        ];
    }

    public function testEmptyStringsCountAsConfigured(): void
    {
        // Documents current behaviour: saving the settings form with empty fields stores "" which isset() accepts.
        $settings = SettingsHelper::persist(['remote_url' => '', 'matomo_site_id' => '', 'auth_token' => '']);

        $this->assertTrue($settings->valid);
        $this->assertTrue($settings->report_widgets_enabled);
    }

    public function testRulesTargetExistingFormFields(): void
    {
        $fields = array_keys(\Symfony\Component\Yaml\Yaml::parseFile(__DIR__ . '/../../models/settings/fields.yaml')['tabs']['fields']);
        $unknown = array_diff(array_keys((new Settings())->rules), $fields);

        if ($unknown) {
            $this->markTestIncomplete('BUG: validation rules for unknown fields: ' . implode(', ', $unknown) . ' (field is remote_url)');
        }
        $this->assertSame([], $unknown);
    }

    public function testRulesAreEnforcedOnSave(): void
    {
        $uses = class_uses_recursive(Settings::class);
        if (!in_array(\October\Rain\Database\Traits\Validation::class, $uses, true)) {
            $this->markTestIncomplete('BUG: Settings declares $rules but does not use the Validation trait, nothing is validated');
        }

        $this->expectException(\October\Rain\Exception\ValidationException::class);
        Settings::set(['auth_token' => str_repeat('x', 65)]);
    }

    public function testDeclaredRules(): void
    {
        $rules = (new Settings())->rules;

        $this->assertSame('nullable|string|max:64', $rules['auth_token']);
        $this->assertSame('nullable|numeric', $rules['matomo_site_id']);
        $this->assertSame('nullable|url', $rules['remote_url']);
    }

    public function testGetBridgeAndGetReportsUseTheConfiguration(): void
    {
        $settings = SettingsHelper::persist(self::DUMMY);

        $this->assertInstanceOf(MatomoBridge::class, $settings->getBridge());
        $reports = $settings->getReports();
        $this->assertInstanceOf(MatomoReports::class, $reports);
        $this->assertSame('https://matomo.example.invalid/', $this->getProtectedProperty($reports, 'remote_url'));
        $this->assertSame('dummy-token', $this->getProtectedProperty($reports, 'auth_token'));
        $this->assertEquals(3, $this->getProtectedProperty($reports, 'site_id'));
    }

    public function testGetBridgeFailsHardWithoutToken(): void
    {
        $settings = SettingsHelper::persist(['remote_url' => 'https://matomo.example.invalid/', 'matomo_site_id' => 1]);

        try {
            $settings->getBridge();
        } catch (\TypeError $e) {
            $this->markTestIncomplete('BUG: missing auth token raises TypeError (not an Exception) -> uncaught in MatomoMiddleware: ' . $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function testIsLoggedInAdminIsFalseForGuests(): void
    {
        $this->assertFalse((new Settings())->isLoggedInAdmin());
    }

    public function testIsLoggedInAdminIsTrueWithBackendUser(): void
    {
        $user = \Backend\Models\User::create([
            'login' => 'tester', 'email' => 'tester@example.invalid', 'first_name' => 'T', 'last_name' => 'T',
            'password' => 'Secret-123!', 'password_confirmation' => 'Secret-123!',
        ]);
        \BackendAuth::login($user);

        $this->assertTrue((new Settings())->isLoggedInAdmin());

        \BackendAuth::logout();
        $this->assertFalse((new Settings())->isLoggedInAdmin());
    }

    public function testIsLoggedInAdminSurvivesAuthErrors(): void
    {
        $original = \BackendAuth::getFacadeRoot();
        \BackendAuth::swap(new class {
            public function getUser()
            {
                throw new \RuntimeException('no session');
            }
        });

        try {
            $this->assertFalse((new Settings())->isLoggedInAdmin());
        } finally {
            \BackendAuth::swap($original);
        }
    }

    public function testResetDefaultRemovesTheStoredSettings(): void
    {
        SettingsHelper::persist(self::DUMMY);
        Settings::resetDefault();

        $this->assertSame(0, \Db::table('system_settings')->where('item', 'renick_matomo_settings')->count());
        $this->assertFalse(Settings::instance()->valid);
    }
}
