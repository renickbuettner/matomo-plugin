<?php namespace Renick\Matomo\Components;

use Cms\Classes\CodeBase;
use Cms\Classes\ComponentBase;
use Renick\Matomo\Models\Settings;

class MatomoJs extends ComponentBase
{
    private $settings;

    public function __construct(CodeBase $cmsObject = null, $properties = [])
    {
        $this->settings = Settings::instance();
        parent::__construct($cmsObject, $properties);
    }

    public function componentDetails()
    {
        return [
            'name' => 'renick.matomo::lang.components.matomojs.name',
            'description' => 'renick.matomo::lang.components.matomojs.description',
        ];
    }

    public function siteId(): string
    {
        return $this->settings->site_id ?? "";
    }

    public function remoteUrl(): string
    {
        return $this->settings->remote_url ?? "";
    }

    public function useCookies(): bool
    {
        return $this->settings->use_cookies ?? false;
    }

    public function isLoggedInAdmin(): bool {
        return $this->settings->isLoggedInAdmin();
    }
}
