<?php
namespace verbb\sociallogin\controllers;

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\models\Settings;

use Craft;
use craft\web\Controller;

use yii\web\Response;

class SettingsController extends Controller
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

    public function actionSaveSettings(): ?Response
    {
        $this->requirePostRequest();

        /* @var Settings $settings */
        $settings = SocialLogin::$plugin->getSettings();
        $settings->setAttributes($this->request->getParam('settings'), false);

        if (!$settings->validate()) {
            Craft::$app->getSession()->setError(Craft::t('social-login', 'Couldn’t save settings.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return null;
        }

        $pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(SocialLogin::$plugin, $settings->toArray());

        if (!$pluginSettingsSaved) {
            Craft::$app->getSession()->setError(Craft::t('social-login', 'Couldn’t save settings.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('social-login', 'Settings saved.'));

        return $this->redirectToPostedUrl();
    }

}
