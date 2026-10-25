<?php namespace Renick\Matomo\Tests\Unit;

use Renick\Matomo\Models\Settings;

/**
 * Test helper: puts the settings model into a known state and drops its in-memory caches.
 */
class SettingsHelper
{
    /** Replaces the cached settings instance by a fresh one holding $values (not persisted). */
    public static function set(array $values, ?Settings $model = null): Settings
    {
        $model ??= new Settings();
        foreach ($values as $key => $value) {
            $model->{$key} = $value;
        }
        static::inject($model);

        return $model;
    }

    /** Persists $values through the real settings API. */
    public static function persist(array $values): Settings
    {
        static::reset();
        Settings::set($values);
        static::flush();

        return Settings::instance();
    }

    public static function inject(Settings $model): void
    {
        $prop = new \ReflectionProperty(\System\Behaviors\SettingsModel::class, 'instances');
        $prop->setAccessible(true);
        $all = $prop->getValue();
        $all['renick_matomo_settings'] = $model;
        $prop->setValue(null, $all);
    }

    public static function flush(): void
    {
        Settings::clearInternalCache();
        \Cache::forget('system::settings.renick_matomo_settings');
    }

    public static function reset(): void
    {
        static::flush();
    }
}
