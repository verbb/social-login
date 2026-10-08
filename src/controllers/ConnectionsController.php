<?php
namespace verbb\sociallogin\controllers;

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\helpers\ConnectionIdentities;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\Response;

use RuntimeException;

class ConnectionsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireCpRequest();
        // These are user account records, not project configuration; production admins must be able to resolve them.
        $this->requireAdmin(false);

        return true;
    }

    public function actionIndex(): Response
    {
        $conflicts = [];

        foreach (SocialLogin::$plugin->getConnections()->getOwnershipConflicts() as $key => $connections) {
            $users = [];

            foreach ($connections as $connection) {
                $userId = (int)$connection['userId'];
                $users[$userId] = Craft::$app->getUsers()->getUserById($userId);
            }

            $handle = $connections[0]['providerHandle'];
            $provider = SocialLogin::$plugin->getProviders()->getProviderByHandle($handle);
            $conflicts[] = [
                'key' => $key,
                'fingerprint' => ConnectionIdentities::fingerprint($connections),
                'providerName' => $provider?->getName() ?? $handle,
                'identifier' => $connections[0]['identifier'],
                'users' => array_filter($users),
            ];
        }

        return $this->renderTemplate('social-login/connections', ['conflicts' => $conflicts]);
    }

    public function actionResolve(): Response
    {
        $this->requirePostRequest();
        $key = $this->request->getRequiredBodyParam('identity');
        $fingerprint = $this->request->getRequiredBodyParam('fingerprint');
        $userId = filter_var($this->request->getRequiredBodyParam('userId'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!is_string($key) || !preg_match('/^[a-f0-9]{64}$/D', $key) || !is_string($fingerprint) || !preg_match('/^[a-f0-9]{64}$/D', $fingerprint) || !$userId || !Craft::$app->getUsers()->getUserById($userId)) {
            throw new BadRequestHttpException('Invalid connection ownership selection.');
        }

        try {
            SocialLogin::$plugin->getConnections()->resolveOwnershipConflict($key, $userId, $fingerprint);
        } catch (RuntimeException $e) {
            Craft::$app->getSession()->setError(Craft::t('social-login', $e->getMessage()));

            return $this->redirect(UrlHelper::cpUrl('social-login/connections'));
        }

        Craft::$app->getSession()->setNotice(Craft::t('social-login', 'Provider account owner saved.'));

        return $this->redirect(UrlHelper::cpUrl('social-login/connections'));
    }
}
