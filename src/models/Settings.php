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
    public bool $enableCpElevatedLogin = false;
    public string $cpLoginTemplate = '';
    public bool $enableRegistration = true;
    public array $userGroups = [];
    public bool $populateProfile = true;
    public bool $syncProfile = false;
    public bool $forceActivate = true;
    public bool $sendActivationEmail = true;
    public ?string $redirectUri = null;

    public array $providers = [];


    // Public Methods
    // =========================================================================

    public function getProviderSettings(): array
    {
        // Merge the provider config from Class, Plugin Settings and Config file. Craft's plugin service
        // does a shallow merge, which doesn't handle partial-setting of provider info in config files.
        // Therefore, we can't rely on `$settings->providers` to give us the full picture.
        // Merge everything together with `ArrayHelper::merge` which will handle recursive arrays as we want
        // because it handles overwriting values better. Config settings take the most precendence.
        return ArrayHelper::merge($this->providers, $this->getStoredProviderSettings(), $this->getConfigProviderSettings());
    }

    public function getStoredProviderSettings(): array
    {
        $pluginInfo = Craft::$app->getPlugins()->getStoredPluginInfo('social-login');
        $providerSettings = $pluginInfo['settings']['providers'] ?? [];

        return is_array($providerSettings) ? $providerSettings : [];
    }

    public function getConfigProviderSettings(): array
    {
        $config = Craft::$app->getConfig()->getConfigFromFile('social-login');
        $providerSettings = is_array($config) ? ($config['providers'] ?? []) : [];

        return is_array($providerSettings) ? $providerSettings : [];
    }

    public function getProviderSettingsForPersistence(?array $providerSettings = null): array
    {
        $providerSettings ??= $this->getStoredProviderSettings();

        return $this->_removeConfigOverrides($providerSettings, $this->getConfigProviderSettings());
    }


    // Private Methods
    // =========================================================================

    private function _removeConfigOverrides(array $settings, array $overrides): array
    {
        foreach ($overrides as $key => $override) {
            if (!array_key_exists($key, $settings)) {
                continue;
            }

            $value = $settings[$key];

            if (is_array($value) && ($value === [] || !array_is_list($value)) && is_array($override) && !array_is_list($override)) {
                $settings[$key] = $this->_removeConfigOverrides($value, $override);

                continue;
            }

            unset($settings[$key]);
        }

        return $settings;
    }

}
