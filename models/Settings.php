<?php namespace Renick\Matomo\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use Renick\Matomo\Classes\Compat\UserResolver;
use Renick\Matomo\Classes\MatomoBridge;
use Renick\Matomo\Classes\MatomoReports;
use System\Behaviors\SettingsModel;

/**
 * Model
 */
class Settings extends Model
{
    use Validation;

    public $implement = [SettingsModel::class];
    public $settingsCode = 'renick_matomo_settings';
    public $settingsFields = 'fields.yaml';

    /**
     * The rules to be applied to the data.
     *
     * @var array
     */
    public $rules = [
        'remote_url' => 'nullable|url',
        'auth_token' => 'nullable|string|max:64',
        'matomo_site_id' => 'nullable|numeric',
    ];

    /**
     * Casts the status to a boolean.
     * @return bool
     */
    public function getUseMiddlewareAttribute(): bool
    {
        return boolval($this->attributes['use_middleware'] ?? false);
    }

    /**
     * Casts the status to a boolean.
     * @return bool
     */
    public function getUseCookiesAttribute(): bool
    {
        return boolval($this->attributes['use_cookies'] ?? false);
    }

    /**
     * Creates a new instance of the bridge based on the user settings.
     * @return MatomoBridge
     */
    public function getBridge(): MatomoBridge
    {
        return new MatomoBridge(
            (string) $this->remote_url,
            $this->auth_token ? (string) $this->auth_token : null,
            (int) $this->matomo_site_id
        );
    }

    /**
     * Creates a new instance of the reports bridge based on the user settings.
     * @return MatomoBridge
     */
    public function getReports(): MatomoReports
    {
        return new MatomoReports(
            (string) $this->remote_url,
            (string) $this->auth_token,
            (int) $this->matomo_site_id
        );
    }

    /**
     * Returns state if Report Widget can be enabled
     * @return bool
     */
    public function getReportWidgetsEnabledAttribute(): bool
    {
        foreach (['auth_token', 'remote_url', 'matomo_site_id'] as $required) {
            if (!isset($this->attributes[$required])) {
                return false;
            }
        };

        return true;
    }

    /**
     * Returns state of configuration.
     * @return bool
     */
    public function getValidAttribute(): bool
    {
        foreach (['remote_url', 'matomo_site_id'] as $required) {
            if (!isset($this->attributes[$required])) {
                return false;
            }
        };

        return true;
    }

    /**
     * We want to detect, if an admin user is logged
     * @return bool
     */
    public function isLoggedInAdmin(): bool {
        return UserResolver::isBackendUserLoggedIn();
    }
}
