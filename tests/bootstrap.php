<?php
/**
 * Locates the October CMS root and loads its test bootstrap (PluginTestCase etc.).
 * Needed because the plugin may be symlinked into plugins/, which breaks relative paths.
 * Order: OCTOBER_ROOT env var, current working directory (php artisan plugin:test), plugins/<author>/<name> layout.
 */
$candidates = [getenv('OCTOBER_ROOT') ?: null, getcwd(), dirname(__DIR__, 3)];

foreach (array_filter($candidates) as $root) {
    if (is_file($root . '/modules/system/tests/bootstrap.php')) {
        require $root . '/modules/system/tests/bootstrap.php';
        return;
    }
}

fwrite(STDERR, "October CMS root not found. Run `php artisan plugin:test Renick.Matomo` from the October root or set OCTOBER_ROOT.\n");
exit(1);
