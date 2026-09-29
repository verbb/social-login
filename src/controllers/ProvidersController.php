<?php
namespace verbb\sociallogin\controllers;

use verbb\sociallogin\SocialLogin;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;

use yii\web\HttpException;
use yii\web\Response;

class ProvidersController extends Controller
{
    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireCpRequest();

        // Locked environments may inspect provider settings, but only writable environments may change them.
        $this->requireAdmin(!in_array($action->id, ['index', 'edit'], true));

        return true;
    }

    public function actionIndex(): Response
    {
        $providers = SocialLogin::$plugin->getProviders()->getAllProviders();

        return $this->renderTemplate('social-login/settings/providers', [
            'providers' => $providers,
        ]);
    }

    public function actionEdit(string $handle): Response
    {
        $provider = SocialLogin::$plugin->getProviders()->getProviderByHandle($handle);

        if (!$provider) {
            throw new HttpException(404);
        }

        return $this->renderTemplate('social-login/settings/providers/_edit', [
            'provider' => $provider,
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $handle = $this->request->getParam('handle');
        $settings = $this->request->getParam('settings');

        $provider = SocialLogin::$plugin->getProviders()->getProviderByHandle($handle);

        if (!$provider) {
            throw new HttpException(404);
        }

        $provider->setAttributes($settings, false);

        if (!SocialLogin::$plugin->getProviders()->saveProvider($provider)) {
            Craft::$app->getSession()->setError(Craft::t('social-login', 'Couldn’t save provider.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'provider' => $provider,
            ]);

            SocialLogin::error(Craft::t('social-login', 'Couldn’t save provider - {e}.', ['e' => Json::encode($provider->getErrors())]));

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('social-login', 'Provider saved.'));

        return $this->redirectToPostedUrl();
    }

}
