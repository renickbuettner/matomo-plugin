<?php namespace Renick\Matomo\Tests\Unit;

use PluginTestCase;

/**
 * Base class of all plugin tests.
 */
abstract class MatomoTestCase extends PluginTestCase
{
    /**
     * October guesses the plugin from the test file path below plugins/. That fails when the plugin directory is
     * a symlink (real path outside of plugins/), so the plugin code is stated explicitly.
     */
    protected function guessPluginCodeFromTest()
    {
        return 'Renick.Matomo';
    }

    public function setUp(): void
    {
        parent::setUp();

        // The in-memory test database only gets its tables during setUp(), but October may have cached
        // "no database" before that, which makes settings models ignore stored records.
        $helper = \System::getFacadeRoot();
        $prop = new \ReflectionProperty($helper, 'hasDatabaseCache');
        $prop->setAccessible(true);
        $prop->setValue($helper, true);

        SettingsHelper::reset();
        $this->forgetAuthenticatedUser();
    }

    /** The backend auth manager is a process-wide singleton, a user logged in by one test must not leak into the next. */
    protected function forgetAuthenticatedUser(): void
    {
        $manager = \Backend\Classes\AuthManager::instance();
        foreach (['user'] as $name) {
            $prop = new \ReflectionProperty(\October\Rain\Auth\Manager::class, $name);
            $prop->setAccessible(true);
            $prop->setValue($manager, null);
        }
    }

    public function tearDown(): void
    {
        SettingsHelper::reset();
        $this->forgetAuthenticatedUser();
        parent::tearDown();
    }
}
