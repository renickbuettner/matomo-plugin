<?php namespace Renick\Matomo\Components;

use Cms\Classes\CodeBase;
use Cms\Classes\ComponentBase;
use Renick\Matomo\Models\Settings;

class MTagJs extends ComponentBase
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
            'name' => 'renick.matomo::lang.components.mtagjs.name',
            'description' => 'renick.matomo::lang.components.mtagjs.description',
        ];
    }

    public function remoteUrl(): string
    {
        $url = (string) ($this->settings->remote_url ?? "");
        return $url === "" || substr($url, -1) === "/" ? $url : "{$url}/";
    }

    public function containerId(): string
    {
        return $this->settings->container_id ?? "";
    }

    public function isLoggedInAdmin(): bool {
        return $this->settings->isLoggedInAdmin();
    }
}
