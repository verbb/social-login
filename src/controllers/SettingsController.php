<?php
namespace verbb\sociallogin\controllers;

use verbb\sociallogin\SocialLogin;

use yii\web\Response;

use verbb\base\controllers\SettingsController as BaseSettingsController;

class SettingsController extends BaseSettingsController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $settings = SocialLogin::$plugin->getSettings();

        return $this->renderTemplate('social-login/settings', [
            'settings' => $settings,
        ]);
    }


    // Protected Methods
    // =========================================================================

    protected function prepareSubmittedSettings(array $settings): array
    {
        // Preserve provider configuration when the general settings form is saved.
        $settings['providers'] = SocialLogin::$plugin->getSettings()->getProviderSettingsForPersistence();

        return $settings;
    }

}
