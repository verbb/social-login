<?php
// Real Craft persistence with synthetic provider profiles; requires an isolated issue-52 database.
$appPath = getenv('VERBB_SOCIAL_LOGIN_TEST_APP');
if (!$appPath || !is_file($appPath . '/bootstrap.php')) {
    throw new RuntimeException('Set VERBB_SOCIAL_LOGIN_TEST_APP to the disposable Craft application.');
}
define('YII_ENABLE_ERROR_HANDLER', false);
require $appPath . '/bootstrap.php';
$_SERVER['SCRIPT_FILENAME'] = $appPath . '/web/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_NAME'] = 'issue52.example.test';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/web.php';

use craft\db\Query;
use craft\elements\User;
use verbb\auth\clients\microsoftentra\provider\MicrosoftEntraResourceOwner;
use verbb\auth\helpers\Session;
use verbb\auth\models\Token;
use verbb\auth\models\UserProfile;
use verbb\sociallogin\providers\MicrosoftEntra;
use verbb\sociallogin\SocialLogin;

if (!preg_match('/;dbname=social_login_issue52(?:;|$)/', $app->getDb()->dsn)) {
    throw new RuntimeException('This fixture requires a disposable database named social_login_issue52.');
}
final class LinkingFixtureWebUser extends craft\web\User
{
    public ?int $loggedInUserId = null;
    public function login(yii\web\IdentityInterface $identity, $duration = 0): bool
    {
        $this->loggedInUserId = $identity->getId();
        return true;
    }
}
final class LinkingFixtureProvider extends MicrosoftEntra
{
    public UserProfile $profile;
    public function getUserProfile(Token $token): UserProfile
    {
        return $this->profile;
    }
}
function linkingCheck(string $label, bool $ok): void
{
    if (!$ok) { throw new RuntimeException('Failed: ' . $label); }
    echo "PASS: $label\n";
}
function linkingCounts(): array
{
    return [(int)User::find()->status(null)->count(), (int)(new Query())->from('{{%social_login_connections}}')->count(), (int)(new Query())->from('{{%auth_oauth_tokens}}')->count()];
}
function linkingProfile(string $id, string $email, ?bool $verified = null): LinkingFixtureProvider
{
    $provider = new LinkingFixtureProvider();
    $provider->profile = new UserProfile(new MicrosoftEntraResourceOwner(['id' => $id, 'mail' => $email, 'userPrincipalName' => $email]));
    if ($verified !== null) { $provider->profile->data['emailVerified'] = $verified; }
    return $provider;
}
function linkingToken(): Token
{
    return new Token(['ownerHandle' => 'social-login', 'providerType' => verbb\auth\providers\MicrosoftEntra::class, 'tokenType' => Token::TOKEN_TYPE_OAUTH2, 'accessToken' => 'synthetic-' . bin2hex(random_bytes(6))]);
}
$plugin = SocialLogin::$plugin;
$settings = $plugin->getSettings();
$settings->populateProfile = false;
$settings->syncProfile = false;
$settings->enableRegistration = true;
$settings->forceActivate = true;
$settings->sendActivationEmail = false;
$app->set('user', new LinkingFixtureWebUser(['identityClass' => User::class, 'enableSession' => false]));
$app->getSession()->open();
$originalEdition = $app->getEdition();
$app->setEdition(craft\enums\CmsEdition::Pro);
$transaction = $app->getDb()->beginTransaction();
try {
    $suffix = bin2hex(random_bytes(6));
    $owner = new User(['username' => "owner-$suffix@example.test", 'email' => "owner-$suffix@example.test"]);
    linkingCheck('create original Craft account', $app->getElements()->saveElement($owner));
    $app->getUsers()->activateUser($owner);
    $before = linkingCounts();
    $provider = linkingProfile("original-$suffix", "different-$suffix@example.test");
    $app->getUser()->setIdentity($owner);
    linkingCheck('original #52: signed-in user can connect a provider with a different email', $plugin->getUsers()->loginOrRegisterUser($provider, linkingToken(), $owner->id, true));
    $afterConnect = linkingCounts();
    linkingCheck('connecting only adds one connection and token, not another user', $afterConnect === [$before[0], $before[1] + 1, $before[2] + 1]);
    $app->getUser()->setIdentity(null);
    linkingCheck('original #52: later provider login reaches the original Craft user', $plugin->getUsers()->loginOrRegisterUser($provider, linkingToken()) && $app->getUser()->loggedInUserId === $owner->id);
    linkingCheck('returning login creates no extra users, connections or tokens', linkingCounts() === $afterConnect);
    $provider->profile->data['email'] = "changed-again-$suffix@example.test";
    linkingCheck('a later provider email change still reaches the original user', $plugin->getUsers()->loginOrRegisterUser($provider, linkingToken()) && $app->getUser()->loggedInUserId === $owner->id);

    $admin = User::find()->admin()->one();
    $provider = linkingProfile("admin-$suffix", $admin->email);
    $before = linkingCounts();
    linkingCheck('recent #52: unknown-verification Entra email cannot sign into an unlinked administrator', !$plugin->getUsers()->loginOrRegisterUser($provider, linkingToken()));
    linkingCheck('recent #52: rejected email match creates no duplicate user, connection or token', linkingCounts() === $before);
    linkingCheck('rejected match explains how to connect the account', str_contains((string)Session::getError('social-login'), 'connect'));
    linkingCheck('repeating the rejected attempt does not create records', !$plugin->getUsers()->loginOrRegisterUser($provider, linkingToken()) && linkingCounts() === $before);

    $app->getUser()->setIdentity($admin);
    linkingCheck('existing administrator can explicitly connect Entra after signing into Craft', $plugin->getUsers()->loginOrRegisterUser($provider, linkingToken(), $admin->id, true));
    $app->getUser()->setIdentity(null);
    linkingCheck('connected administrator can subsequently sign in without a verified email claim', $plugin->getUsers()->loginOrRegisterUser($provider, linkingToken()) && $app->getUser()->loggedInUserId === $admin->id);

    foreach (['active', 'pending', 'inactive', 'suspended', 'locked'] as $state) {
        $email = "$state-$suffix@example.test";
        $user = new User(['username' => $email, 'email' => $email]);
        linkingCheck("create $state collision fixture", $app->getElements()->saveElement($user));
        $app->getDb()->createCommand()->update('{{%users}}', ['active' => in_array($state, ['active', 'suspended', 'locked']), 'pending' => $state === 'pending', 'suspended' => $state === 'suspended', 'locked' => $state === 'locked'], ['id' => $user->id])->execute();
        $before = linkingCounts();
        $collision = linkingProfile("collision-$state-$suffix", strtoupper($email));
        linkingCheck("case-insensitive email collision with $state account cannot create records", !$plugin->getUsers()->loginOrRegisterUser($collision, linkingToken()) && linkingCounts() === $before);
    }

    $before = linkingCounts();
    $verified = linkingProfile("verified-$suffix", $owner->email, true);
    linkingCheck('verified first-time email matching still reaches the existing account', $plugin->getUsers()->loginOrRegisterUser($verified, linkingToken()) && $app->getUser()->loggedInUserId === $owner->id && linkingCounts()[0] === $before[0]);
    $verifiedNew = linkingProfile("verified-new-$suffix", "verified-new-$suffix@example.test", true);
    linkingCheck('new verified users still register, activate and sign in', $plugin->getUsers()->loginOrRegisterUser($verifiedNew, linkingToken()));

    $settings->enableRegistration = false;
    $before = linkingCounts();
    linkingCheck('disabled registration cannot create users or credentials', !$plugin->getUsers()->loginOrRegisterUser(linkingProfile("disabled-$suffix", "disabled-$suffix@example.test"), linkingToken()) && linkingCounts() === $before);
    $settings->enableRegistration = true;

    foreach (['email', 'username', 'profile'] as $collisionType) {
        $listener = function($event) use ($owner, $collisionType) {
            if ($collisionType === 'email') { $event->user->email = $owner->email; }
            if ($collisionType === 'username') { $event->user->username = $owner->username; }
            if ($collisionType === 'profile') { $event->user->fullName = 'https://invalid.example.test'; }
        };
        $plugin->getUsers()->on(verbb\sociallogin\services\Users::EVENT_BEFORE_REGISTER, $listener);
        $before = linkingCounts();
        try {
            linkingCheck("registration validates event-modified $collisionType before persisting", !$plugin->getUsers()->loginOrRegisterUser(linkingProfile("event-$collisionType-$suffix", "event-$collisionType-$suffix@example.test"), linkingToken()) && linkingCounts() === $before);
        } finally {
            $plugin->getUsers()->off(verbb\sociallogin\services\Users::EVENT_BEFORE_REGISTER, $listener);
        }
    }

    $before = linkingCounts();
    $new = linkingProfile("new-$suffix", "new-$suffix@example.test");
    linkingCheck('genuinely new unverified user still follows activation instead of logging in', !$plugin->getUsers()->loginOrRegisterUser($new, linkingToken()));
    $created = User::find()->status(null)->email("new-$suffix@example.test")->one();
    linkingCheck('new unverified registration retains its user and connection for activation', $created && !$created->active && linkingCounts() === [$before[0] + 1, $before[1] + 1, $before[2] + 1]);
    $app->getUsers()->activateUser($created);
    linkingCheck('new user can log in through the saved identity after activation', $plugin->getUsers()->loginOrRegisterUser($new, linkingToken()) && $app->getUser()->loggedInUserId === $created->id);
} finally {
    $transaction->rollBack();
    $app->setEdition($originalEdition);
    $app->getSession()->close();
}
