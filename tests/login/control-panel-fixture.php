<?php
// CP rendering and callback feedback with real Craft persistence and a synthetic OAuth exchange.
$appPath = getenv('VERBB_SOCIAL_LOGIN_TEST_APP');
if (!$appPath || !is_file($appPath . '/bootstrap.php')) { throw new RuntimeException('Set VERBB_SOCIAL_LOGIN_TEST_APP.'); }
define('YII_ENABLE_ERROR_HANDLER', false);
require $appPath . '/bootstrap.php';
$_SERVER['SCRIPT_FILENAME'] = $appPath . '/web/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/index.php?p=admin/myaccount';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_NAME'] = 'issue52.example.test';
$_GET['p'] = 'admin/myaccount';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/web.php';

use craft\elements\User;
use craft\web\View;
use verbb\auth\Auth;
use verbb\auth\helpers\Session;
use verbb\auth\models\Token;
use verbb\auth\models\UserProfile;
use verbb\auth\clients\microsoftentra\provider\MicrosoftEntraResourceOwner;
use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\controllers\AuthController;
use verbb\sociallogin\providers\MicrosoftEntra;

if (!preg_match('/;dbname=social_login_issue52(?:;|$)/', $app->getDb()->dsn)) { throw new RuntimeException('Requires disposable social_login_issue52 database.'); }
function cpCheck(string $label, bool $ok): void
{
    if (!$ok) { throw new RuntimeException('Failed: ' . $label); }
    echo "PASS: $label\n";
}
final class CpFixtureProvider extends MicrosoftEntra
{
    public UserProfile $profile;
    public function getUserProfile(Token $token): UserProfile { return $this->profile; }
}
final class CpFixtureProviders extends verbb\sociallogin\services\Providers
{
    public CpFixtureProvider $provider;
    public function getAllEnabledProviders(): array { return $this->provider->enabled ? [$this->provider] : []; }
    public function getProviderByHandle(string $handle): ?verbb\sociallogin\base\Provider { return $handle === $this->provider->handle ? $this->provider : null; }
}
final class CpFixtureOAuth extends verbb\auth\services\OAuth
{
    public array $transaction;
    public function prepareCallback(?string $ownerHandle = null): ?yii\web\Response { return null; }
    public function claimCallback(?string $ownerHandle = null, ?string $transactionId = null): array { return $this->transaction; }
    public function callback(string $ownerHandle, verbb\auth\base\OAuthProviderInterface $provider, string|int|null $reference = null): Token
    {
        return new Token(['ownerHandle' => 'social-login', 'providerType' => verbb\auth\providers\MicrosoftEntra::class, 'tokenType' => Token::TOKEN_TYPE_OAUTH2, 'accessToken' => 'synthetic-cp-token']);
    }
}
// Composer path repositories may resolve differently on the test host; publish this checkout's assets.
Craft::setAlias('@verbb/sociallogin', dirname(__DIR__, 2) . '/src');
$plugin = SocialLogin::$plugin;
$plugin->getSettings()->populateProfile = false;
$plugin->getSettings()->sendActivationEmail = false;
$app->set('user', new craft\web\User(['identityClass' => User::class, 'enableSession' => false]));
$app->getSession()->open();
$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
$admin = User::find()->admin()->one();
$app->getUser()->setIdentity($admin);
$originalEdition = $app->getEdition();
$app->setEdition(craft\enums\CmsEdition::Pro);
$transaction = $app->getDb()->beginTransaction();
try {
    $suffix = bin2hex(random_bytes(6));
    $provider = new CpFixtureProvider(['enabled' => true, 'cpLoginEnabled' => true]);
    $provider->profile = new UserProfile(new MicrosoftEntraResourceOwner(['id' => "cp-$suffix", 'mail' => $admin->email]));
    $providers = new CpFixtureProviders(['provider' => $provider]);
    $plugin->set('providers', $providers);
    $html = $admin->getSidebarHtml(false);
    cpCheck('the real Craft user sidebar offers Connect on your own unconnected account', str_contains($html, 'Connect Microsoft Entra') && str_contains($html, 'social-login/auth/connect'));
    $other = new User(['username' => "cp-$suffix@example.test", 'email' => "cp-$suffix@example.test"]);
    cpCheck('create other-account fixture', $app->getElements()->saveElement($other));
    $staticSidebar = new craft\events\DefineHtmlEvent(['static' => true]);
    $admin->trigger(User::EVENT_DEFINE_SIDEBAR_HTML, $staticSidebar);
    cpCheck('static user views do not receive interactive provider controls', !str_contains($staticSidebar->html, 'social-login/auth/'));
    cpCheck('viewing another user does not offer controls that operate on your account', !str_contains($other->getSidebarHtml(false), 'social-login/auth/'));
    $provider->enabled = false;
    cpCheck('disabled providers are not offered', !str_contains($admin->getSidebarHtml(false), 'social-login/auth/'));
    $provider->enabled = true;
    $app->getConfig()->getGeneral()->allowAdminChanges = false;
    cpCheck('own-account connection controls work when project configuration is locked', str_contains($admin->getSidebarHtml(false), 'Connect Microsoft Entra'));

    // OAuth callbacks normally arrive as site requests, even for a CP-originated login.
    $app->getRequest()->setIsCpRequest(false);
    $app->getUser()->setIdentity(null);
    Session::set('origin', 'http://issue52.example.test/admin/login');
    Session::set('redirect', 'http://issue52.example.test/admin/myaccount');
    $oauth = new CpFixtureOAuth();
    $oauth->transaction = ['context' => ['providerHandle' => $provider->handle, 'isCpRequest' => true, 'isConnect' => false], 'initiatingUserId' => null];
    Auth::getInstance()->set('oauth', $oauth);
    $controller = new AuthController('auth', $plugin);
    $controller->actionCallback();
    cpCheck('a rejected CP-originated callback creates a visible CP error notification', str_contains(json_encode($app->getSession()->getFlash('cp-notification-error')), 'connect this provider'));

    $app->getUser()->setIdentity($admin);
    $oauth->transaction['context']['isConnect'] = true;
    $oauth->transaction['initiatingUserId'] = $admin->id;
    $response = $controller->actionCallback();
    cpCheck('a successful explicit connect returns to the account screen', str_contains($response->getHeaders()->get('location'), 'admin/myaccount'));
    cpCheck('a successful CP-originated callback creates a visible CP notice', str_contains(json_encode($app->getSession()->getFlash('cp-notification-notice')), 'connected'));
    $app->getRequest()->setIsCpRequest(true);
    cpCheck('the connected account offers Disconnect instead of Connect', str_contains($admin->getSidebarHtml(false), 'Disconnect Microsoft Entra') && !str_contains($admin->getSidebarHtml(false), 'social-login/auth/connect'));
} finally {
    $transaction->rollBack();
    $app->getConfig()->getGeneral()->allowAdminChanges = true;
    $app->setEdition($originalEdition);
    $app->getSession()->close();
}
