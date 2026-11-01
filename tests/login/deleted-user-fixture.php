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
use verbb\auth\models\Token;
use verbb\auth\models\UserProfile;
use verbb\sociallogin\providers\MicrosoftEntra;
use verbb\sociallogin\SocialLogin;

if (!preg_match('/;dbname=social_login_issue52(?:;|$)/', $app->getDb()->dsn)) {
    throw new RuntimeException('This fixture requires a disposable database named social_login_issue52.');
}
final class DeletedUserFixtureWebUser extends craft\web\User
{
    public ?int $loggedInUserId = null;
    public function login(yii\web\IdentityInterface $identity, $duration = 0): bool
    {
        $this->loggedInUserId = $identity->getId();
        return true;
    }
}
final class DeletedUserFixtureProvider extends MicrosoftEntra
{
    public UserProfile $profile;
    public function getUserProfile(Token $token): UserProfile
    {
        return $this->profile;
    }
}
function deletedUserCheck(string $label, bool $ok): void
{
    if (!$ok) { throw new RuntimeException('Failed: ' . $label); }
    echo "PASS: $label\n";
}
function deletedUserCounts(): array
{
    return [(int)User::find()->status(null)->count(), (int)(new Query())->from('{{%social_login_connections}}')->count(), (int)(new Query())->from('{{%auth_oauth_tokens}}')->count()];
}
function deletedUserProfile(string $id, string $email, ?bool $verified = null): DeletedUserFixtureProvider
{
    $provider = new DeletedUserFixtureProvider();
    $provider->profile = new UserProfile(new MicrosoftEntraResourceOwner(['id' => $id, 'mail' => $email, 'userPrincipalName' => $email]));
    if ($verified !== null) { $provider->profile->data['emailVerified'] = $verified; }
    return $provider;
}
function deletedUserToken(): Token
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
$app->set('user', new DeletedUserFixtureWebUser(['identityClass' => User::class, 'enableSession' => false]));
$app->getSession()->open();
$originalEdition = $app->getEdition();
$app->setEdition(craft\enums\CmsEdition::Pro);
$transaction = $app->getDb()->beginTransaction();
try {
    $suffix = bin2hex(random_bytes(6));
    $owner = new User(['username' => "owner-$suffix@example.test", 'email' => "owner-$suffix@example.test"]);
    deletedUserCheck('create target for explicit connections', $app->getElements()->saveElement($owner));
    $app->getUsers()->activateUser($owner);

    // Craft retains connections when their user is soft-deleted. Exercise recovery on the next callback.
    foreach (['register', 'disabled', 'cancelled', 'connect', 'review'] as $mode) {
        $deletedProvider = deletedUserProfile("deleted-$mode-$suffix", "deleted-$mode-$suffix@example.test", true);
        deletedUserCheck("create deletion fixture ($mode)", $plugin->getUsers()->loginOrRegisterUser($deletedProvider, deletedUserToken()));
        $deletedUser = $app->getUsers()->getUserById($app->getUser()->loggedInUserId);
        $oldConnection = $plugin->getConnections()->getAllConnectionsByProviderIdentifier($deletedProvider->handle, $deletedProvider->profile->id)[0];
        $oldToken = $oldConnection->getToken();
        deletedUserCheck("warm connection cache ($mode)", $plugin->getConnections()->getConnectionById($oldConnection->id) !== null);

        if ($mode === 'review') {
            $app->getDb()->createCommand()->update('{{%social_login_connections}}', ['identityKey' => null], ['id' => $oldConnection->id])->execute();
        }

        deletedUserCheck("soft-delete connected user ($mode)", $app->getElements()->deleteElement($deletedUser));
        deletedUserCheck("deleted user is unavailable but connection remains ($mode)", $app->getUsers()->getUserById($deletedUser->id) === null && (new Query())->from('{{%social_login_connections}}')->where(['id' => $oldConnection->id])->exists());
        $before = deletedUserCounts();
        $app->getUser()->loggedInUserId = null;
        $settings->enableRegistration = $mode !== 'disabled';
        $cancelRegistration = function($event) { $event->isValid = false; };

        if ($mode === 'cancelled') {
            $plugin->getUsers()->on(verbb\sociallogin\services\Users::EVENT_BEFORE_REGISTER, $cancelRegistration);
        }

        if ($mode === 'connect') {
            $app->getUser()->setIdentity($owner);
            deletedUserCheck('deleted owner does not prevent an explicit connection to the current user', $plugin->getUsers()->loginOrRegisterUser($deletedProvider, deletedUserToken(), $owner->id, true));
            $app->getUser()->setIdentity(null);
        } elseif ($mode === 'register') {
            deletedUserCheck('issue #64: a deleted user can register again with the same provider identity', $plugin->getUsers()->loginOrRegisterUser($deletedProvider, deletedUserToken()));
            deletedUserCheck('registration creates a new user without restoring the deleted account', $app->getUser()->loggedInUserId !== $deletedUser->id && User::find()->id($deletedUser->id)->status(null)->trashed(true)->exists());
        } else {
            deletedUserCheck("deleted identity still respects $mode policy", !$plugin->getUsers()->loginOrRegisterUser($deletedProvider, deletedUserToken()) && $app->getUser()->loggedInUserId === null);
        }

        $plugin->getUsers()->off(verbb\sociallogin\services\Users::EVENT_BEFORE_REGISTER, $cancelRegistration);

        if ($mode === 'review') {
            deletedUserCheck('deleting an owner does not release a pending ownership review or its token', deletedUserCounts() === $before && (new Query())->from('{{%auth_oauth_tokens}}')->where(['id' => $oldToken->id])->exists());
        } elseif (in_array($mode, ['disabled', 'cancelled'], true)) {
            deletedUserCheck("failed attempt keeps the deleted user's connection and token ($mode)", deletedUserCounts() === $before && (new Query())->from('{{%social_login_connections}}')->where(['id' => $oldConnection->id])->exists() && (new Query())->from('{{%auth_oauth_tokens}}')->where(['id' => $oldToken->id])->exists());

            // Without a verified claim, only the kept connection can identify the restored user.
            unset($deletedProvider->profile->data['emailVerified']);
            $settings->enableRegistration = false;
            deletedUserCheck("restore the deleted user ($mode)", $app->getElements()->restoreElement(User::find()->id($deletedUser->id)->status(null)->trashed(true)->one()));
            $links = $plugin->getConnections()->getAllConnectionsByProviderIdentifier($deletedProvider->handle, $deletedProvider->profile->id);
            deletedUserCheck("restored user signs in through the kept connection ($mode)", $plugin->getUsers()->loginOrRegisterUser($deletedProvider, deletedUserToken()) && $app->getUser()->loggedInUserId === $deletedUser->id && count($links) === 1 && $links[0]->id === $oldConnection->id);
        } else {
            deletedUserCheck("stale connection and token are removed ($mode)", !(new Query())->from('{{%social_login_connections}}')->where(['id' => $oldConnection->id])->exists() && !(new Query())->from('{{%auth_oauth_tokens}}')->where(['id' => $oldToken->id])->exists());
            deletedUserCheck("deleted connection is removed from the service cache ($mode)", $plugin->getConnections()->getConnectionById($oldConnection->id) === null);
            $links = $plugin->getConnections()->getAllConnectionsByProviderIdentifier($deletedProvider->handle, $deletedProvider->profile->id);
            $expectedUserId = $mode === 'connect' ? $owner->id : $app->getUser()->loggedInUserId;
            deletedUserCheck("replacement identity has exactly one owner ($mode)", count($links) === 1 && $links[0]->userId === $expectedUserId);
            $after = deletedUserCounts();
            deletedUserCheck("subsequent login reuses the replacement identity ($mode)", $plugin->getUsers()->loginOrRegisterUser($deletedProvider, deletedUserToken()) && $app->getUser()->loggedInUserId === $expectedUserId && deletedUserCounts() === $after);
        }

        $settings->enableRegistration = true;
    }

    // Account restrictions must never be mistaken for a deleted owner and bypassed by registration.
    foreach (['pending', 'inactive', 'suspended', 'locked'] as $state) {
        $provider = deletedUserProfile("restricted-$state-$suffix", "restricted-$state-$suffix@example.test", true);
        deletedUserCheck("create connected $state fixture", $plugin->getUsers()->loginOrRegisterUser($provider, deletedUserToken()));
        $userId = $app->getUser()->loggedInUserId;
        $app->getDb()->createCommand()->update('{{%users}}', [
            'active' => in_array($state, ['suspended', 'locked']),
            'pending' => $state === 'pending',
            'suspended' => $state === 'suspended',
            'locked' => $state === 'locked',
            'lockoutDate' => $state === 'locked' ? gmdate('Y-m-d H:i:s') : null,
        ], ['id' => $userId])->execute();
        $before = deletedUserCounts();
        $connectionsBefore = (new Query())->from('{{%social_login_connections}}')->orderBy('id')->all();
        $tokensBefore = (new Query())->from('{{%auth_oauth_tokens}}')->orderBy('id')->all();
        $app->getUser()->loggedInUserId = null;
        deletedUserCheck("$state owner cannot log in or register a replacement", !$plugin->getUsers()->loginOrRegisterUser($provider, deletedUserToken()) && $app->getUser()->loggedInUserId === null && deletedUserCounts() === $before);
        deletedUserCheck("$state owner keeps its connection and token", (new Query())->from('{{%social_login_connections}}')->orderBy('id')->all() === $connectionsBefore && (new Query())->from('{{%auth_oauth_tokens}}')->orderBy('id')->all() === $tokensBefore);
    }
} finally {
    $transaction->rollBack();
    $app->setEdition($originalEdition);
    $app->getSession()->close();
}
