<?php namespace Renick\Matomo\Models;

use Model;
use Renick\Matomo\Classes\MatomoBridge;
use Renick\Matomo\Classes\MatomoReports;
use System\Behaviors\SettingsModel;

/**
 * Model
 */
class Settings extends Model
{
    public $implement = [SettingsModel::class];
    public $settingsCode = 'renick_matomo_settings';
    public $settingsFields = 'fields.yaml';

    /**
     * The rules to be applied to the data.
     *
     * @var array
     */
    public $rules = [
        'url' => 'url',
        'auth_token' => 'string|max:64',
        'site_id' => 'numeric',
    ];

    /**
     * Casts the status to a boolean.
     * @return bool
     */
    public function getUseMiddlewareAttribute(): bool
    {
        return boolval($this->attributes['use_middleware']);
    }

    /**
     * Casts the status to a boolean.
     * @return bool
     */
    public function getUseCookiesAttribute(): bool
    {
        return boolval($this->attributes['use_cookies']);
    }

    /**
     * Creates a new instance of the bridge based on the user settings.
     * @return MatomoBridge
     */
    public function getBridge(): MatomoBridge
    {
        return new MatomoBridge(
            $this->remote_url,
            $this->auth_token,
            $this->site_id
        );
    }

    /**
     * Creates a new instance of the reports bridge based on the user settings.
     * @return MatomoBridge
     */
    public function getReports(): MatomoReports
    {
        return new MatomoReports(
            $this->remote_url,
            $this->auth_token,
            $this->site_id
        );
    }
}