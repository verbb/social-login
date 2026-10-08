<?php
// Controller authorization and request validation against an installed disposable Craft application.
$appPath = getenv('VERBB_SOCIAL_LOGIN_TEST_APP');

if (!$appPath || !is_file($appPath . '/bootstrap.php')) {
    throw new RuntimeException('Set VERBB_SOCIAL_LOGIN_TEST_APP to the disposable Craft application.');
}

define('YII_ENABLE_ERROR_HANDLER', false);
require $appPath . '/bootstrap.php';
$_SERVER['SCRIPT_FILENAME'] = $appPath . '/web/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/admin/social-login/connections';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_NAME'] = 'issue60.example.test';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/web.php';

use craft\elements\User;
use verbb\sociallogin\controllers\ConnectionsController;
use verbb\sociallogin\helpers\ConnectionIdentities;
use verbb\sociallogin\models\Connection;
use verbb\sociallogin\SocialLogin;

if (!preg_match('/;dbname=social_login_issue60(?:;|$)/', $app->getDb()->dsn)) {
    throw new RuntimeException('This fixture requires a disposable database named social_login_issue60.');
}

final class ReviewFixtureWebUser extends craft\web\User
{
    public function checkPermission($permissionName, $params = [], $allowCaching = true): bool
    {
        // A non-admin with CP access must still be denied by the controller's administrator guard.
        return $permissionName === 'accessCp' || parent::checkPermission($permissionName, $params, $allowCaching);
    }
}

function reviewCheck(string $message, bool $condition): void
{
    if (!$condition) {
        throw new RuntimeException('Failed: ' . $message);
    }

    echo "PASS: $message\n";
}

function reviewReject(string $message, callable $callback, string $exception): void
{
    try {
        $callback();
    } catch (Throwable $e) {
        if (!$e instanceof $exception) { throw $e; }
        reviewCheck($message, true);
        return;
    }

    throw new RuntimeException('Not rejected: ' . $message);
}

$app->set('user', new ReviewFixtureWebUser(['identityClass' => User::class, 'enableSession' => false]));
$app->getSession()->open();
$app->getConfig()->getGeneral()->allowAdminChanges = false;
$request = $app->getRequest();
$request->setIsCpRequest(true);
$admin = User::find()->admin()->one();
$originalEdition = $app->getEdition();
$app->getProjectConfig()->readOnly = false;
$app->setEdition(craft\enums\CmsEdition::Pro);
$app->getProjectConfig()->readOnly = true;
$transaction = $app->getDb()->beginTransaction();

try {
    $email = 'review-' . bin2hex(random_bytes(6)) . '@example.test';
    $other = new User(['username' => $email, 'email' => $email]);
    reviewCheck('review fixture creates its non-admin user', $app->getElements()->saveElement($other));
    $controller = new ConnectionsController('connections', SocialLogin::$plugin);
    $app->getUser()->setIdentity($other);
    reviewReject('a non-admin with CP access cannot review ownership', fn() => $controller->runAction('index'), yii\web\ForbiddenHttpException::class);
    $app->getUser()->setIdentity($admin);
    $request->setIsCpRequest(false);
    reviewReject('the ownership controller cannot run as a site request', fn() => $controller->runAction('index'), yii\web\BadRequestHttpException::class);
    $request->setIsCpRequest(true);
    reviewCheck('an administrator can review with admin configuration changes disabled', $controller->runAction('index') instanceof yii\web\Response);
    reviewReject('ownership cannot be changed using GET', fn() => $controller->runAction('resolve'), yii\web\MethodNotAllowedHttpException::class);
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $request->setBodyParams([]);
    reviewReject('ownership POST requires Craft CSRF validation', fn() => $controller->runAction('resolve'), yii\web\BadRequestHttpException::class);
    $csrf = [$request->csrfParam => $request->getCsrfToken()];
    $request->setBodyParams($csrf + ['identity' => 'bad-key', 'fingerprint' => 'bad-fingerprint', 'userId' => $admin->id]);
    reviewReject('ownership POST rejects malformed selections', fn() => $controller->runAction('resolve'), yii\web\BadRequestHttpException::class);

    $identifier = 'review-' . bin2hex(random_bytes(6));
    foreach ([$admin->id, $other->id] as $id) {
        $app->getDb()->createCommand()->insert('{{%social_login_connections}}', ['userId' => $id, 'providerHandle' => 'microsoftEntra', 'identifier' => $identifier, 'identityKey' => null])->execute();
    }

    $key = Connection::identityKey('microsoftEntra', $identifier);
    $connections = SocialLogin::$plugin->getConnections();
    $fingerprint = ConnectionIdentities::fingerprint($connections->getOwnershipConflicts()[$key]);
    $request->setBodyParams($csrf + ['identity' => $key, 'fingerprint' => $fingerprint, 'userId' => $admin->id]);
    $app->getUser()->setIdentity($other);
    reviewReject('a non-admin cannot submit an ownership decision', fn() => $controller->runAction('resolve'), yii\web\ForbiddenHttpException::class);
    $app->getUser()->setIdentity($admin);
    $response = $controller->runAction('resolve');
    reviewCheck('valid administrator POST resolves and redirects to the CP review', $response->statusCode === 302 && str_contains($response->getHeaders()->get('location'), 'admin/social-login/connections') && !isset($connections->getOwnershipConflicts()[$key]));
    reviewCheck('ownership selection does not delete either Craft user', User::find()->id([$admin->id, $other->id])->status(null)->count() == 2);
} finally {
    $transaction->rollBack();
    $app->getProjectConfig()->readOnly = false;
    $app->setEdition($originalEdition);
    $app->getSession()->close();
}
