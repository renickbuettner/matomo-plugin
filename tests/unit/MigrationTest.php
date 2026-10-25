<?php namespace Renick\Matomo\Tests\Unit;

use Db;

class MigrationTest extends MatomoTestCase
{
    private function migration()
    {
        return require __DIR__ . '/../../updates/1-0-4_matomo_update_database.php';
    }

    private function store(string $item, string $json): void
    {
        Db::table('system_settings')->updateOrInsert(['item' => $item], ['value' => $json]);
    }

    private function value(string $item): ?string
    {
        return Db::table('system_settings')->where('item', $item)->value('value');
    }

    public function testUpRenamesSiteIdToMatomoSiteId(): void
    {
        $this->store('renick_matomo_settings', '{"remote_url":"https:\/\/m.example.invalid\/","site_id":"5","auth_token":"t"}');

        $this->migration()->up();

        $stored = json_decode($this->value('renick_matomo_settings'), true);
        $this->assertSame('5', $stored['matomo_site_id']);
        $this->assertArrayNotHasKey('site_id', $stored);
        $this->assertSame('t', $stored['auth_token']);
    }

    public function testDownRestoresTheOldKey(): void
    {
        $this->store('renick_matomo_settings', '{"matomo_site_id":"5"}');

        $this->migration()->down();

        $this->assertSame(['site_id' => '5'], json_decode($this->value('renick_matomo_settings'), true));
    }

    public function testOtherSettingsRowsAreUntouched(): void
    {
        $other = '{"site_id":"9"}';
        $this->store('some_other_plugin_settings', $other);
        $this->store('renick_matomo_settings', '{"site_id":"5"}');

        $this->migration()->up();

        $this->assertSame($other, $this->value('some_other_plugin_settings'));
    }

    public function testUpWithoutStoredSettingsDoesNothing(): void
    {
        $this->migration()->up();

        $this->assertNull($this->value('renick_matomo_settings'));
    }

    public function testMigrationIsIdempotent(): void
    {
        $this->store('renick_matomo_settings', '{"matomo_site_id":"5"}');

        $this->migration()->up();

        $this->assertSame(['matomo_site_id' => '5'], json_decode($this->value('renick_matomo_settings'), true));
    }
}
