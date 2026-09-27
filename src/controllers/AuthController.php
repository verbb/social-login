<?php
namespace verbb\sociallogin\controllers;

use verbb\sociallogin\SocialLogin;

use Craft;
use craft\web\Controller;

use yii\web\Response;

use verbb\auth\Auth;
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
        // Don't require CSRF validation for callback requests
        if ($action->id === 'callback') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionLogin(): Response
    {
        $providerHandle = $this->request->getRequiredParam('provider');

        try {
            if (!($provider = SocialLogin::$plugin->getProviders()->getProviderByHandle($providerHandle))) {
                Session::setError('social-login', Craft::t('social-login', "Unable to find provider “{provider}”.", ['provider' => $providerHandle]));

                // We might be triggering this via a URL, not a POST request, so always redirect
                return $this->redirect($this->request->getReferrer());
            }

            // Redirect to the provider platform to login and authorize
            return Auth::getInstance()->getOAuth()->connect('social-login', $provider, $providerHandle, [
                'providerHandle' => $providerHandle,
                'data' => $this->request->getParam('data'),
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

            // Redirect back as a failure
            return $this->redirect($this->request->getReferrer());
        }
    }

    public function actionCallback(): Response
    {
        $oauth = Auth::getInstance()->getOAuth();

        if ($response = $oauth->prepareCallback('social-login')) {
            return $response;
        }

        $transaction = $oauth->claimCallback('social-login');

        // Get both the origin (failure) and redirect (success) URLs
        $origin = Session::get('origin');
        $redirect = Session::get('redirect');

        // Get the provider we're current authorizing
        if (!($providerHandle = Session::get('providerHandle'))) {
            Session::setError('social-login', Craft::t('social-login', 'Unable to find provider.'));

            return $this->redirect($origin);
        }

        if (!($provider = SocialLogin::$plugin->getProviders()->getProviderByHandle($providerHandle))) {
            Session::setError('social-login', Craft::t('social-login', "Unable to find provider “{provider}”.", ['provider' => $providerHandle]));

            return $this->redirect($origin);
        }

        try {
            // Fetch the access token from the provider and create a Token for us to use
            $token = $oauth->callback('social-login', $provider, $providerHandle);

            if (!$token) {
                Session::setError('social-login', Craft::t('social-login', 'Unable to fetch token.'));

                return $this->redirect($origin);
            }

            // Handle the login or registration of the user
            if (!SocialLogin::$plugin->getUsers()->loginOrRegisterUser($provider, $token, $transaction['initiatingUserId'])) {
                Session::setError('social-login', Craft::t('social-login', 'An error occurred when logging in.'));

                return $this->redirect($origin);
            }

            // Everything was a success!
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

        // Redirect back as a failure
        return $this->redirect($origin);
    }

    public function actionConnect(): Response
    {
        return $this->actionLogin();
    }

    public function actionDisconnect(): ?Response
    {
        $providerHandle = $this->request->getRequiredParam('provider');
        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$currentUser) {
            Session::setError('social-login', Craft::t('social-login', 'User not logged in.'));

            // We might be triggering this via a URL, not a POST request, so always redirect
            return $this->redirect($this->request->getReferrer());
        }

        if (!($provider = SocialLogin::$plugin->getProviders()->getProviderByHandle($providerHandle))) {
            Session::setError('social-login', Craft::t('social-login', "Unable to find provider “{provider}”.", ['provider' => $providerHandle]));

            // We might be triggering this via a URL, not a POST request, so always redirect
            return $this->redirect($this->request->getReferrer());
        }

        if (!SocialLogin::$plugin->getConnections()->deleteConnectionByUserAndProvider($currentUser->id, $providerHandle)) {
            Session::setError('social-login', Craft::t('social-login', 'Unable to disconnect.'));

            // We might be triggering this via a URL, not a POST request, so always redirect
            return $this->redirect($this->request->getReferrer());
        }

        Session::setNotice('social-login', Craft::t('social-login', '{provider} disconnected.', ['provider' => $provider->getName()]));

        // We might be triggering this via a URL, not a POST request, so always redirect
        return $this->redirect($this->request->getReferrer());
    }

}
