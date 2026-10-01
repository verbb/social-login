<?php
namespace verbb\sociallogin\controllers;

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\base\Provider;

use Craft;
use craft\web\Controller;

use yii\web\Response;

use verbb\auth\Auth;
use verbb\auth\helpers\Redirect;
use verbb\auth\helpers\Session;

use Throwable;

class AuthController extends Controller
{
    // Properties
    // =========================================================================

    protected array|int|bool $allowAnonymous = ['login', 'callback'];


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if ($action->id === 'callback') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionLogin(): Response
    {
        return $this->_startAuthorization(false);
    }

    public function actionConnect(): Response
    {
        return $this->_startAuthorization(true);
    }

    public function actionCallback(): Response
    {
        $oauth = Auth::getInstance()->getOAuth();

        if ($response = $oauth->prepareCallback('social-login')) {
            return $response;
        }

        $transaction = $oauth->claimCallback('social-login');
        $origin = Session::get('origin');
        $redirect = Session::get('redirect');
        $providerHandle = $transaction['context']['providerHandle'] ?? null;
        $isConnect = ($transaction['context']['isConnect'] ?? null) === true;
        $isCpRequest = ($transaction['context']['isCpRequest'] ?? null) === true;

        if (!$providerHandle || !($provider = SocialLogin::$plugin->getProviders()->getProviderByHandle($providerHandle))) {
            Session::setError('social-login', Craft::t('social-login', 'Unable to find provider.'));

            return $this->redirect($origin);
        }

        if (!$this->_isAuthorizationAllowed($provider, $isConnect, $isCpRequest, $transaction['initiatingUserId'] ?? null)) {
            Session::setError('social-login', Craft::t('social-login', 'This login method is no longer available.'));

            return $this->redirect($origin);
        }

        try {
            $token = $oauth->callback('social-login', $provider, $providerHandle);

            if (!SocialLogin::$plugin->getUsers()->loginOrRegisterUser($provider, $token, $transaction['initiatingUserId'] ?? null, $isConnect)) {
                if (!Session::getError('social-login')) {
                    Session::setError('social-login', Craft::t('social-login', 'An error occurred when logging in.'));
                }

                return $this->redirect($origin);
            }

            Session::setNotice('social-login', Craft::t('social-login', "{provider} connected.", ['provider' => $provider->getName()]));

            return $this->redirect($redirect);
        } catch (Throwable $e) {
            Session::setError('social-login', Craft::t('social-login', 'Unable to process callback for “{provider}”: “{message}”', [
                'provider' => $providerHandle,
                'message' => $e->getMessage(),
            ]));

            SocialLogin::error('Unable to process callback for “{provider}”: “{message}” {file}:{line}', [
                'provider' => $providerHandle,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        return $this->redirect($origin);
    }

    public function actionDisconnect(): ?Response
    {
        $this->requirePostRequest();

        $providerHandle = $this->request->getRequiredParam('provider');
        $currentUser = Craft::$app->getUser()->getIdentity();
        $returnUrl = Redirect::safeReferrer($this->request->getReferrer());

        if (!$currentUser) {
            Session::setError('social-login', Craft::t('social-login', 'User not logged in.'));

            return $this->redirect($returnUrl);
        }

        if (!($provider = SocialLogin::$plugin->getProviders()->getProviderByHandle($providerHandle))) {
            Session::setError('social-login', Craft::t('social-login', "Unable to find provider “{provider}”.", ['provider' => $providerHandle]));

            return $this->redirect($returnUrl);
        }

        if (!SocialLogin::$plugin->getConnections()->deleteConnectionByUserAndProvider($currentUser->id, $providerHandle)) {
            Session::setError('social-login', Craft::t('social-login', 'Unable to disconnect.'));

            return $this->redirect($returnUrl);
        }

        Session::setNotice('social-login', Craft::t('social-login', '{provider} disconnected.', ['provider' => $provider->getName()]));

        return $this->redirect($returnUrl);
    }


    // Private Methods
    // =========================================================================

    private function _startAuthorization(bool $isConnect): Response
    {
        $providerHandle = $this->request->getRequiredParam('provider');
        $returnUrl = Redirect::safeReferrer($this->request->getReferrer());

        try {
            $provider = SocialLogin::$plugin->getProviders()->getProviderByHandle($providerHandle);

            if (!$provider) {
                Session::setError('social-login', Craft::t('social-login', "Unable to find provider “{provider}”.", ['provider' => $providerHandle]));

                return $this->redirect($returnUrl);
            }

            if (!$this->_isAuthorizationAllowed($provider, $isConnect, $this->request->getIsCpRequest(), Craft::$app->getUser()->getId())) {
                Session::setError('social-login', Craft::t('social-login', 'This login method is not available.'));

                return $this->redirect($returnUrl);
            }

            return Auth::getInstance()->getOAuth()->connect('social-login', $provider, $providerHandle, [
                'providerHandle' => $providerHandle,
                'data' => $this->request->getParam('data'),
                'isConnect' => $isConnect,
                'isCpRequest' => $this->request->getIsCpRequest(),
                'loginName' => $this->request->getParam('loginName'),
                'rememberMe' => $this->request->getParam('rememberMe'),
            ]);
        } catch (Throwable $e) {
            SocialLogin::error('Unable to authorize login for “{provider}”: “{message}” {file}:{line}', [
                'provider' => $providerHandle,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            Session::setError('social-login', Craft::t('social-login', "Unable to authorize login for “{provider}”.", ['provider' => $providerHandle]));

            return $this->redirect($returnUrl);
        }
    }

    private function _isAuthorizationAllowed(Provider $provider, bool $isConnect, bool $isCpRequest, mixed $initiatingUserId): bool
    {
        if ($isConnect) {
            return $provider->enabled && $initiatingUserId !== null;
        }

        return $provider->canLogin($isCpRequest);
    }
}
