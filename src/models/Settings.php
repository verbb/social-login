<?php
namespace verbb\sociallogin\models;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public bool $enableLogin = true;
    public bool $enableCpLogin = true;
    public string $cpLoginTemplate = '';
    public bool $enableRegistration = true;
    public array $userGroups = [];
    public bool $populateProfile = true;

    public array $providers = [];


    // Public Methods
    // =========================================================================

    public function getProviderSettings(): array
    {
        // Merge the provider config from Class, Plugin Settings and Config file. Craft's plugin service
        // does a shallow merge, which doesn't handle partial-setting of provider info in config files.
        // Therefore, we can't rely on `$settings->providers` to give us the full picture.
        $config = Craft::$app->getConfig()->getConfigFromFile('social-login');
        $pluginInfo = Craft::$app->getPlugins()->getStoredPluginInfo('social-login');

        $configSettings = $config['providers'] ?? [];
        $pluginInfoSettings = $pluginInfo['settings']['providers'] ?? [];

        // Merge everything together with `ArrayHelper::merge` which will handle recursive arrays as we want
        // because it handles overwriting values better. Config settings take the most precendence.
        return ArrayHelper::merge($this->providers, $pluginInfoSettings, $configSettings);
    }

}
