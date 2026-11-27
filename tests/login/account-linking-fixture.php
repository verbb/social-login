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
use verbb\sociallogin\helpers\ConnectionIdentities;
use verbb\sociallogin\models\Connection;
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
        linkingCheck("refused email match does not reveal that the $state account needs activation", !str_contains((string)Session::getError('social-login'), 'activated'));
    }

    $before = linkingCounts();
    $verified = linkingProfile("verified-$suffix", '  ' . strtoupper($owner->email) . '  ', true);
    linkingCheck('verified first-time matching permits ASCII case differences and surrounding whitespace', $plugin->getUsers()->loginOrRegisterUser($verified, linkingToken()) && $app->getUser()->loggedInUserId === $owner->id && linkingCounts()[0] === $before[0]);

    // Synthetic profiles exercise the local comparison boundary, not a live provider's email policy.
    foreach (['müller' => 'muller', 'café' => 'cafe'] as $storedDomain => $assertedDomain) {
        $email = "comparison-$suffix@$storedDomain.example";
        $candidate = new User(['username' => $email, 'email' => $email]);
        linkingCheck("create international email fixture for $storedDomain", $app->getElements()->saveElement($candidate));
        $app->getUsers()->activateUser($candidate);
        $assertedEmail = "comparison-$suffix@$assertedDomain.example";
        $databaseMatch = User::find()->status(null)->email($assertedEmail)->one();
        linkingCheck("database comparison baseline for $storedDomain", $app->getDb()->getIsMysql() ? $databaseMatch?->id === $candidate->id : $databaseMatch === null);
        $before = linkingCounts();
        $app->getUser()->loggedInUserId = null;
        $settings->enableRegistration = false;
        $collision = linkingProfile("comparison-$storedDomain-$suffix", $assertedEmail, true);
        linkingCheck("verified but differently spelled email cannot log in as $storedDomain", !$plugin->getUsers()->loginOrRegisterUser($collision, linkingToken()) && $app->getUser()->loggedInUserId === null && linkingCounts() === $before);
        $settings->enableRegistration = true;
        $exact = linkingProfile("exact-$storedDomain-$suffix", $email, true);
        linkingCheck("identical international email can still match $storedDomain", $plugin->getUsers()->loginOrRegisterUser($exact, linkingToken()) && $app->getUser()->loggedInUserId === $candidate->id && linkingCounts()[0] === $before[0]);
    }

    $mapped = new User(['username' => "mapped-$suffix@example.test", 'email' => "actual-$suffix@example.test"]);
    linkingCheck('create user with a separate matching attribute', $app->getElements()->saveElement($mapped));
    $match = new ReflectionMethod($plugin->getUsers(), '_matchExistingUser');
    $byUsername = linkingProfile("username-$suffix", $mapped->username, true);
    $byUsername->matchUserDestination = 'username';
    linkingCheck('email-to-username matching retains its configured destination', $match->invoke($plugin->getUsers(), $byUsername, $byUsername->profile)?->id === $mapped->id);
    $byId = linkingProfile($mapped->username, "unrelated-$suffix@example.test");
    $byId->matchUserSource = 'id';
    $byId->matchUserDestination = 'username';
    linkingCheck('provider-ID matching remains independent of email', $match->invoke($plugin->getUsers(), $byId, $byId->profile)?->id === $mapped->id);

    foreach (['verified' => true, 'unknown' => null] as $label => $verified) {
        $email = "forced-$label-$suffix@example.test";
        $provider = linkingProfile("forced-$label-$suffix", $email, $verified);
        $before = linkingCounts();
        linkingCheck("forced activation allows a new $label user to sign in", $plugin->getUsers()->loginOrRegisterUser($provider, linkingToken()));
        $created = User::find()->status(null)->email($email)->one();
        linkingCheck("forced activation persists one active $label user and its identity", $created?->active && $app->getUser()->loggedInUserId === $created->id && linkingCounts() === [$before[0] + 1, $before[1] + 1, $before[2] + 1]);
        linkingCheck("forced activation does not change the $label email claim", $provider->profile->getEmailVerified() === $verified);
        $after = linkingCounts();
        linkingCheck("returning $label user signs in through the saved identity", $plugin->getUsers()->loginOrRegisterUser($provider, linkingToken()) && $app->getUser()->loggedInUserId === $created->id && linkingCounts() === $after);
    }

    // Forced activation trusts an email the provider didn't verify, so a later match by that email may be its real owner.
    // The match is held for an administrator instead of joining the owner to the account the registration created.
    $email = "claimed-$suffix@example.test";
    $claimant = linkingProfile("claimant-$suffix", $email);
    $app->getUser()->loggedInUserId = null;
    linkingCheck('unknown-verification registration signs in under forced activation', $plugin->getUsers()->loginOrRegisterUser($claimant, linkingToken()));
    $claimed = User::find()->status(null)->email($email)->one();
    linkingCheck('the registration records that its email was not verified', $plugin->getConnections()->getConnectionByUserAndProvider($claimed->id, $claimant->handle)?->unverifiedRegistration === true);
    linkingCheck('a returning sign-in keeps the record', $plugin->getUsers()->loginOrRegisterUser($claimant, linkingToken()) && $plugin->getConnections()->getConnectionByUserAndProvider($claimed->id, $claimant->handle)?->unverifiedRegistration === true);
    $before = linkingCounts();
    $app->getUser()->loggedInUserId = null;
    $emailOwner = linkingProfile("claimed-owner-$suffix", $email, true);
    linkingCheck('the verified owner of the email is not signed in to the registered account', !$plugin->getUsers()->loginOrRegisterUser($emailOwner, linkingToken()) && $app->getUser()->loggedInUserId === null);
    linkingCheck('the verified owner is told the account needs an ownership review', str_contains((string)Session::getError('social-login'), 'ownership review'));
    linkingCheck('the owner\'s provider account is held for review without a user or token', linkingCounts() === [$before[0], $before[1] + 1, $before[2]]);
    $claimantKey = Connection::identityKey($claimant->handle, "claimant-$suffix");
    $ownerKey = Connection::identityKey($emailOwner->handle, "claimed-owner-$suffix");
    $conflicts = $plugin->getConnections()->getOwnershipConflicts();
    linkingCheck('both provider accounts are listed for administrator review', isset($conflicts[$claimantKey], $conflicts[$ownerKey]));
    linkingCheck('the registration\'s provider account can no longer sign in until reviewed', !$plugin->getUsers()->loginOrRegisterUser($claimant, linkingToken()) && $app->getUser()->loggedInUserId === null);
    $before = linkingCounts();
    linkingCheck('the owner retrying stays in review without adding records', !$plugin->getUsers()->loginOrRegisterUser($emailOwner, linkingToken()) && linkingCounts() === $before);
    $plugin->getConnections()->resolveOwnershipConflict($ownerKey, $claimed->id, ConnectionIdentities::fingerprint($conflicts[$ownerKey]));
    $plugin->getConnections()->resolveOwnershipConflict($claimantKey, 0, ConnectionIdentities::fingerprint($plugin->getConnections()->getOwnershipConflicts()[$claimantKey]));
    linkingCheck('choosing none removes the registration\'s connection and token', !$plugin->getConnections()->getAllConnectionsByProviderIdentifier($claimant->handle, "claimant-$suffix") && linkingCounts()[1] === $before[1] - 1);
    linkingCheck('the confirmed owner signs in to the account', $plugin->getUsers()->loginOrRegisterUser($emailOwner, linkingToken()) && $app->getUser()->loggedInUserId === $claimed->id);
    $app->getUser()->loggedInUserId = null;
    linkingCheck('the removed provider account can no longer sign in to the account', !$plugin->getUsers()->loginOrRegisterUser($claimant, linkingToken()) && $app->getUser()->loggedInUserId === null);
    linkingCheck('a verified match still reaches an account registered with a verified email', $plugin->getUsers()->loginOrRegisterUser(linkingProfile("forced-verified-second-$suffix", "forced-verified-$suffix@example.test", true), linkingToken()) && $app->getUser()->loggedInUserId === User::find()->status(null)->email("forced-verified-$suffix@example.test")->one()?->id);

    // A provider stating the email is unverified must not be overridden, or its verified owner could later share the account.
    $email = "forced-unverified-$suffix@example.test";
    $claimant = linkingProfile("forced-unverified-$suffix", $email, false);
    $before = linkingCounts();
    $app->getUser()->loggedInUserId = null;
    linkingCheck('forced activation leaves a new explicitly unverified user awaiting activation', !$plugin->getUsers()->loginOrRegisterUser($claimant, linkingToken()) && $app->getUser()->loggedInUserId === null);
    $created = User::find()->status(null)->email($email)->one();
    linkingCheck('explicitly unverified registration retains its inactive user and connection', $created && !$created->active && linkingCounts() === [$before[0] + 1, $before[1] + 1, $before[2] + 1]);
    linkingCheck('explains that the explicitly unverified account must be activated', str_contains((string)Session::getError('social-login'), 'activated'));
    linkingCheck('a verified owner of the address cannot sign in to the inactive account', !$plugin->getUsers()->loginOrRegisterUser(linkingProfile("forced-owner-$suffix", $email, true), linkingToken()) && $app->getUser()->loggedInUserId === null);
    linkingCheck('retrying does not activate the explicitly unverified user', !$plugin->getUsers()->loginOrRegisterUser($claimant, linkingToken()) && !User::find()->status(null)->id($created->id)->one()->active);

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

    $settings->forceActivate = false;
    foreach (['verified' => true, 'unknown' => null, 'unverified' => false] as $label => $verified) {
        $before = linkingCounts();
        $email = "pending-$label-$suffix@example.test";
        $new = linkingProfile("pending-$label-$suffix", $email, $verified);
        linkingCheck("disabled forced activation leaves a new $label user awaiting activation", !$plugin->getUsers()->loginOrRegisterUser($new, linkingToken()));
        $created = User::find()->status(null)->email($email)->one();
        linkingCheck("new $label registration retains its user and connection for activation", $created && !$created->active && linkingCounts() === [$before[0] + 1, $before[1] + 1, $before[2] + 1]);
        $settings->forceActivate = true;
        $app->getSession()->removeAllFlashes();
        linkingCheck("forced activation does not activate an existing inactive $label user", !$plugin->getUsers()->loginOrRegisterUser($new, linkingToken()) && !User::find()->status(null)->id($created->id)->one()->active);
        linkingCheck("returning inactive $label user is told activation is required", str_contains((string)Session::getError('social-login'), 'activated'));
        $settings->forceActivate = false;
        $app->getUsers()->activateUser($created);
        linkingCheck("new $label user can sign in after activation", $plugin->getUsers()->loginOrRegisterUser($new, linkingToken()) && $app->getUser()->loggedInUserId === $created->id);
    }
} finally {
    $transaction->rollBack();
    $app->setEdition($originalEdition);
    $app->getSession()->close();
}
