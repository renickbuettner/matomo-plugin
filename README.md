# Renick.Matomo

Just a simple OctoberCMS plugin for embedding matomo analytics. These features are provided by the initial release of
this plugin:

- [x] store matomo api key as well as page id
- [x] middleware for matomo server-side tracking
- [x] component for matomo javascript integration
- [x] component for matomo tag manager javascript integration
- [x] components hide the matomo script when an admin user is logged in
- [x] integration of analytic reports within the OctoberCMS backend

## Compatibility

Requires PHP 8.0+ (the code uses `match`, `str_contains` and `str_ends_with`). The backend reports use the Laravel HTTP
client (`Http::asForm()`, `Http::fake()` in tests), which exists since Laravel 7, so reports need OctoberCMS 3.x or 4.x. On
OctoberCMS 1.x/2.x (Laravel 5.5/6) the `Http` alias points to October's own, differently shaped HTTP class and the reports do not work.

| OctoberCMS | Laravel | PHP (core) |
|------------|---------|------------|
| 1.0        | 5.5     | 7.0+       |
| 1.1 / 2.x  | 6       | 7.2+       |
| 3.0 - 3.7  | 9 / 10  | 8.0.2+     |
| 4.x        | 12      | 8.1+       |

Sources: `composer.json` of `octobercms/library` and the OctoberCMS documentation.

To install this plugin, you can just run the following command:
```bash
php artisan plugin:install Renick.Matomo --from=git@github.com:renickbuettner/matomo-plugin.git
```

Feel free to ask, raise ideas, mind bugs or contribute on the Github repository. Please create a new issue or pull
request.

---

Future ideas:

- [ ] measure impact of accessing plugins config from database on site performance - may add support for in-memory cache?
- [ ] add more report widgets (api is prepared...)
- [ ] what use-cases do we may need?

## Tests

Unit tests (PHPUnit, October `PluginTestCase`, in-memory SQLite) live in `tests/`. Run them from the OctoberCMS root:

```bash
php artisan plugin:test Renick.Matomo
```
