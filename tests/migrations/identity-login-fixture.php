<?php
// Run against an installed disposable Craft site after the identity migration. Provider responses and login are local fakes.
$appPath = getenv('VERBB_SOCIAL_LOGIN_TEST_APP');

if (!$appPath || !is_file($appPath . '/bootstrap.php')) {
    throw new RuntimeException('Set VERBB_SOCIAL_LOGIN_TEST_APP to the disposable Craft application.');
}

require $appPath . '/bootstrap.php';
$_SERVER['SCRIPT_FILENAME'] = $appPath . '/web/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_NAME'] = 'issue60.example.test';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/web.php';

use craft\db\Query;
use craft\elements\User;
use verbb\auth\clients\microsoftentra\provider\MicrosoftEntraResourceOwner;
use verbb\auth\models\Token;
use verbb\auth\models\UserProfile;
use verbb\sociallogin\models\Connection;
use verbb\sociallogin\providers\MicrosoftEntra;
use verbb\sociallogin\SocialLogin;

if (!preg_match('/;dbname=social_login_issue60(?:;|$)/', $app->getDb()->dsn)) {
    throw new RuntimeException('This fixture requires a disposable database named social_login_issue60.');
}

final class IdentityFixtureWebUser extends craft\web\User
{
    public ?int $loggedInUserId = null;

    public function login(yii\web\IdentityInterface $identity, $duration = 0): bool
    {
        $this->loggedInUserId = $identity->getId();
        return true;
    }
}

final class IdentityFixtureProvider extends MicrosoftEntra
{
    public UserProfile $profile;

    public function getUserProfile(Token $token): UserProfile
    {
        return $this->profile;
    }
}

function identityCheck(string $description, bool $condition): void
{
    if (!$condition) {
        throw new RuntimeException("Failed: $description");
    }

    echo "PASS: $description\n";
}

$plugin = SocialLogin::$plugin;
$plugin->getSettings()->populateProfile = false;
$plugin->getSettings()->syncProfile = false;
$app->set('user', new IdentityFixtureWebUser(['identityClass' => User::class, 'enableSession' => false]));
$app->getSession()->open();
$originalEdition = $app->getEdition();
$app->setEdition(craft\enums\CmsEdition::Pro);
$transaction = $app->getDb()->beginTransaction();

try {
    $suffix = bin2hex(random_bytes(6));
    $users = [];

    foreach (['original', 'shared'] as $name) {
        $email = "$name-$suffix@example.test";
        $user = new User(['username' => $email, 'email' => $email]);

        if (!$app->getElements()->saveElement($user)) {
            throw new RuntimeException('Could not create the synthetic Craft user: ' . json_encode($user->getErrors()));
        }

        $app->getUsers()->activateUser($user);
        $users[] = $user;
    }

    [$original, $shared] = $users;
    $token = new Token([
        'ownerHandle' => 'social-login',
        'providerType' => verbb\auth\providers\MicrosoftEntra::class,
        'tokenType' => Token::TOKEN_TYPE_OAUTH2,
        'accessToken' => 'synthetic-token',
    ]);
    $identifier = "immutable-object-$suffix";
    $connection = new Connection(['userId' => $original->id, 'providerHandle' => 'microsoftEntra', 'identifier' => $identifier]);
    identityCheck('initial identity connection is saved', $plugin->getConnections()->upsertConnection($connection, $token));

    $provider = new IdentityFixtureProvider();
    $provider->profile = new UserProfile(new MicrosoftEntraResourceOwner([
        'id' => $identifier,
        'mail' => "renamed-$suffix@example.test",
        'userPrincipalName' => "renamed-$suffix@example.test",
    ]));
    $userCount = User::find()->status(null)->count();
    $connectionCount = (new Query())->from('{{%social_login_connections}}')->count();
    $app->getUser()->setIdentity(null);

    identityCheck('changed email and UPN still sign into the existing identity owner', $plugin->getUsers()->loginOrRegisterUser($provider, $token) && $app->getUser()->loggedInUserId === $original->id);
    identityCheck('changed email does not register another Craft user or connection', User::find()->status(null)->count() === $userCount && (new Query())->from('{{%social_login_connections}}')->count() === $connectionCount);

    $before = (new Query())->from('{{%social_login_connections}}')->orderBy('id')->all();
    $tokensBefore = (new Query())->from('{{%auth_oauth_tokens}}')->orderBy('id')->all();
    $app->getUser()->setIdentity($shared);
    identityCheck('connecting that identity while signed into a shared account is rejected', !$plugin->getUsers()->loginOrRegisterUser($provider, $token, $shared->id, true));
    identityCheck('rejected connect preserves connections and tokens', (new Query())->from('{{%social_login_connections}}')->orderBy('id')->all() === $before && (new Query())->from('{{%auth_oauth_tokens}}')->orderBy('id')->all() === $tokensBefore);
    // Reproduce legacy cross-user ownership without assigning either user a canonical key.
    $db = $app->getDb();
    $db->createCommand()->update('{{%social_login_connections}}', ['identityKey' => null], ['id' => $connection->id])->execute();
    $db->createCommand()->insert('{{%social_login_connections}}', [
        'userId' => $shared->id,
        'providerHandle' => 'microsoftEntra',
        'identifier' => $identifier,
        'identityKey' => null,
    ])->execute();
    $before = (new Query())->from('{{%social_login_connections}}')->orderBy('id')->all();
    $tokensBefore = (new Query())->from('{{%auth_oauth_tokens}}')->orderBy('id')->all();
    $app->getUser()->setIdentity(null);
    $app->getUser()->loggedInUserId = null;
    identityCheck('an unresolved legacy identity cannot log in', !$plugin->getUsers()->loginOrRegisterUser($provider, $token) && $app->getUser()->loggedInUserId === null);
    identityCheck('an unresolved identity cannot fall through to new-user registration', User::find()->status(null)->count() === $userCount);
    $app->getUser()->setIdentity($shared);
    identityCheck('an unresolved identity cannot be claimed by connecting while signed in', !$plugin->getUsers()->loginOrRegisterUser($provider, $token, $shared->id, true));
    identityCheck('direct saves cannot claim an unresolved identity', !$plugin->getConnections()->saveConnection(new Connection([
        'userId' => $original->id,
        'providerHandle' => 'microsoftEntra',
        'identifier' => $identifier,
    ]), $token));
    identityCheck('all rejected unresolved requests preserve connections and tokens', (new Query())->from('{{%social_login_connections}}')->orderBy('id')->all() === $before && (new Query())->from('{{%auth_oauth_tokens}}')->orderBy('id')->all() === $tokensBefore);
    $key = Connection::identityKey('microsoftEntra', $identifier);
    $conflicts = $plugin->getConnections()->getOwnershipConflicts();
    identityCheck('the unresolved identity is available for administrator review', isset($conflicts[$key]));
    $plugin->getConnections()->resolveOwnershipConflict($key, $original->id, verbb\sociallogin\helpers\ConnectionIdentities::fingerprint($conflicts[$key]));
    identityCheck('ownership resolution keeps both Craft users and only the chosen link', User::find()->status(null)->count() === $userCount && count($plugin->getConnections()->getAllConnectionsByProviderIdentifier('microsoftEntra', $identifier)) === 1 && !isset($plugin->getConnections()->getOwnershipConflicts()[$key]));
    $app->getUser()->setIdentity(null);
    identityCheck('resolved identity signs into the chosen owner despite its changed email', $plugin->getUsers()->loginOrRegisterUser($provider, $token) && $app->getUser()->loggedInUserId === $original->id);
} finally {
    $transaction->rollBack();
    $app->setEdition($originalEdition);
    $app->getSession()->close();
}
