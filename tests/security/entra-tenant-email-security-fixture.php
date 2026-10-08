<?php

use craft\elements\User;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Token\AccessToken;
use verbb\auth\clients\microsoftentra\provider\MicrosoftEntraResourceOwner;
use verbb\auth\models\Token;
use verbb\auth\models\UserProfile;
use verbb\sociallogin\models\Connection;
use verbb\sociallogin\models\Settings;
use verbb\sociallogin\providers\Google;
use verbb\sociallogin\providers\MicrosoftEntra;
use verbb\sociallogin\services\Connections;
use verbb\sociallogin\SocialLogin;

$vendorPath = getenv('VERBB_SOCIAL_LOGIN_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';
require $vendorPath . '/autoload.php';
require $vendorPath . '/yiisoft/yii2/Yii.php';
require $vendorPath . '/craftcms/cms/src/Craft.php';

spl_autoload_register(static function(string $class): void {
    $prefix = 'verbb\\sociallogin\\';

    if (str_starts_with($class, $prefix)) {
        require dirname(__DIR__, 2) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
}, true, true);

final class EntraEmailFixtureApp extends yii\base\Component
{
    public string $language = 'en-US';
    public string $sourceLanguage = 'en-US';

    public function getI18n(): yii\i18n\I18N
    {
        return new yii\i18n\I18N(['translations' => ['*' => ['class' => yii\i18n\PhpMessageSource::class]]]);
    }
}

final class EntraEmailFixtureProvider extends MicrosoftEntra
{
    public function getRedirectUri(): ?string
    {
        return 'https://craft.example.test/callback';
    }
}

final class EntraEmailFixtureConnections extends Connections
{
    public ?Connection $existing = null;

    public function getConnectionByUserAndProvider(int $userId, string $providerHandle): ?Connection
    {
        return $this->existing;
    }
}

function entraAssert(bool $condition, string $description): void
{
    if (!$condition) {
        throw new RuntimeException($description);
    }

    $GLOBALS['entraChecks']++;
}

function entraResponse(mixed $data): Response
{
    return new Response(200, ['Content-Type' => 'application/json'], json_encode($data));
}

function entraFixture(array $settings = [], ?array $member = null, mixed $organization = null, ?array $profile = null): array
{
    $member ??= $GLOBALS['entraMember'];
    $profile ??= $member;
    $organization ??= ['value' => [$GLOBALS['entraOrganization']]];
    $history = new ArrayObject();
    $handler = HandlerStack::create(new MockHandler([
        entraResponse($profile),
        entraResponse($member),
        $organization instanceof Response || $organization instanceof Throwable ? $organization : entraResponse($organization),
    ]));
    $handler->push(Middleware::tap(function($request, $options) use ($history) {
        $history[] = [$request, $options];
    }));
    $provider = new EntraEmailFixtureProvider([
        'clientId' => 'fixture-client',
        'clientSecret' => 'fixture-secret',
        'tenant' => '11111111-2222-3333-4444-555555555555',
        'trustTenantEmail' => true,
        'trustedEmailDomains' => 'example.com',
    ]);
    $provider->getOAuthProvider()->setHttpClient(new Client(['handler' => $handler]));
    $provider->setAttributes($settings, false);
    $token = new Token();
    $token->setToken(new AccessToken(['access_token' => 'fixture-access-token']));
    $userProfile = $provider->getUserProfile($token);

    return [$provider, $userProfile, $history];
}

$GLOBALS['entraChecks'] = 0;
$GLOBALS['entraMember'] = [
    'id' => 'member-object-id',
    'mail' => 'alex@example.com',
    'userPrincipalName' => 'alex@example.com',
    'userType' => 'Member',
    'externalUserState' => null,
];
$GLOBALS['entraOrganization'] = [
    'id' => '11111111-2222-3333-4444-555555555555',
    'verifiedDomains' => [['name' => 'example.com'], ['name' => 'example.onmicrosoft.com']],
];
Craft::$app = new EntraEmailFixtureApp();
$connections = new EntraEmailFixtureConnections();
$plugin = (new ReflectionClass(SocialLogin::class))->newInstanceWithoutConstructor();
$plugin->set('connections', $connections);
$settings = new Settings(['enableRegistration' => false, 'populateProfile' => false]);
(new ReflectionProperty(craft\base\Plugin::class, '_settings'))->setValue($plugin, $settings);
SocialLogin::$plugin = $plugin;

[$provider, $profile, $history] = entraFixture();
entraAssert(count($history) === 1, 'Fetching a profile must not check tenant trust for an already-connected account.');
entraAssert($provider->canMatchEmail($profile), 'A tenant member with matching UPN and verified, allowed domain should qualify.');
entraAssert($profile->getEmailVerified() === null, 'Tenant trust must never claim mailbox verification.');
entraAssert(count($history) === 3, 'Trust must use both the authenticated member and organization endpoints.');
entraAssert($provider->canMatchEmail($profile) && count($history) === 3, 'The same profile should reuse checks only within this authentication.');
foreach (array_slice($history->getArrayCopy(), 1) as [$request, $options]) {
    entraAssert($request->getUri()->getHost() === 'graph.microsoft.com' && $request->getUri()->getScheme() === 'https', 'Checks must use the fixed HTTPS Graph origin.');
    entraAssert($request->getHeaderLine('Authorization') === 'Bearer fixture-access-token', 'Checks must authenticate with the profile token.');
    entraAssert(!str_contains((string)$request->getUri(), 'fixture-access-token'), 'Access tokens must not appear in request URLs.');
    entraAssert($options['allow_redirects'] === false && $options['verify'] === true && $options['timeout'] === 10, 'Graph checks must require TLS, have a timeout, and refuse redirects.');
}

$user = (new ReflectionClass(User::class))->newInstanceWithoutConstructor();
$user->id = 123;
$user->email = 'alex@example.com';
$user->admin = false;
entraAssert($provider->canMatchEmail($profile, $user), 'An unconnected non-admin Craft account may be matched.');
$user->admin = true;
entraAssert(!$provider->canMatchEmail($profile, $user), 'The exception must never link a Craft administrator.');
$user->admin = false;
$user->email = 'someone-else@example.com';
entraAssert(!$provider->canMatchEmail($profile, $user), 'Database collation must not widen the approved email match.');
$user->email = 'alex@example.com';
$connections->existing = new Connection(['identifier' => 'previous-member-object-id']);
entraAssert(!$provider->canMatchEmail($profile, $user), 'Reassigned mail must not replace a saved provider identity.');
$connections->existing = null;

entraAssert(!$provider->canMatchEmail(clone $profile), 'A copied profile must not inherit private matching authority.');
$profile->data['email'] = 'victim@example.com';
entraAssert(!$provider->canMatchEmail($profile), 'Mutating the email must invalidate matching authority.');
$profile->data['email'] = 'alex@example.com';
$profile->data['id'] = 'another-object-id';
entraAssert(!$provider->canMatchEmail($profile), 'Mutating the identity must invalidate matching authority.');
$profile->data['id'] = 'member-object-id';
$provider->tenant = 'different.example.com';
entraAssert(!$provider->canMatchEmail($profile), 'Changing the tenant must invalidate cached authority.');
$provider->tenant = $GLOBALS['entraOrganization']['id'];
$provider->trustedEmailDomains = 'example.com, another.example.com';
entraAssert(!$provider->canMatchEmail($profile), 'Changing allowed domains must invalidate cached authority.');

foreach (['', 'common', ' COMMON ', 'organizations', 'consumers', '9188040d-6c67-4c5b-b112-36a304b66dad', 'https://example.com', 'example.com/path', 'example.com?x=y', '127.0.0.1'] as $tenant) {
    [$provider, $profile, $history] = entraFixture(['tenant' => $tenant]);
    entraAssert(!$provider->canMatchEmail($profile) && count($history) === 1, 'Unsafe tenant must fail without trust requests: ' . $tenant);
    entraAssert(!$provider->validate(['tenant']), 'Unsafe tenant must fail settings validation: ' . $tenant);
}
foreach (['', '*.example.com', 'https://example.com', 'example.com.evil/', 'example.com, *', '$ENTRA_FIXTURE_UNDEFINED'] as $domains) {
    [$provider, $profile, $history] = entraFixture(['trustedEmailDomains' => $domains]);
    entraAssert(!$provider->canMatchEmail($profile) && count($history) === 1, 'Invalid domain list must fail closed: ' . $domains);
    entraAssert(!$provider->validate(['trustedEmailDomains']), 'Invalid domains must fail settings validation: ' . $domains);
}
foreach ([['trustTenantEmail' => false], ['matchUserSource' => 'id'], ['matchUserDestination' => 'username'], ['trustedEmailDomains' => 'other.example.com']] as $settingsOverride) {
    [$provider, $profile, $history] = entraFixture($settingsOverride);
    entraAssert(!$provider->canMatchEmail($profile) && count($history) === 1, 'Trust must not expand other matching modes or domains.');
}

foreach ([
    ['userType' => 'Guest'], ['userType' => null], ['externalUserState' => 'Accepted'],
    ['externalUserState' => 'PendingAcceptance'], ['userPrincipalName' => 'alex_example.com#EXT#@example.onmicrosoft.com'],
    ['userPrincipalName' => 'someone-else@example.com'], ['userPrincipalName' => null],
] as $overrides) {
    [$provider, $profile] = entraFixture(member: array_merge($GLOBALS['entraMember'], $overrides));
    entraAssert(!$provider->canMatchEmail($profile), 'Guest, external, or mismatched UPN must be rejected: ' . json_encode($overrides));
}
$member = $GLOBALS['entraMember'];
unset($member['externalUserState']);
[$provider, $profile] = entraFixture(member: $member);
entraAssert(!$provider->canMatchEmail($profile), 'Missing member evidence must fail closed.');
[$provider, $profile] = entraFixture(member: array_merge($GLOBALS['entraMember'], ['id' => 'other-id']), profile: $GLOBALS['entraMember']);
entraAssert(!$provider->canMatchEmail($profile), 'The member endpoint must describe the same provider identity.');
[$provider, $profile] = entraFixture(member: array_merge($GLOBALS['entraMember'], ['mail' => 'victim@example.com']), profile: $GLOBALS['entraMember']);
entraAssert(!$provider->canMatchEmail($profile), 'The member endpoint must confirm the same email.');

foreach ([
    ['value' => []], ['value' => [$GLOBALS['entraOrganization'], $GLOBALS['entraOrganization']]],
    ['value' => [array_merge($GLOBALS['entraOrganization'], ['id' => 'another-tenant'])]],
    ['value' => [array_merge($GLOBALS['entraOrganization'], ['verifiedDomains' => [['name' => 'example.com.evil']]])]],
    ['value' => [['id' => $GLOBALS['entraOrganization']['id']]]], ['value' => 'invalid'],
    new Response(302, ['Location' => 'https://untrusted.example.test']), new Response(403), new Response(429), new Response(500),
    new Response(200, [], 'not-json'), new Response(200, [], 'null'), new RuntimeException('fixture-token-must-not-leak'),
] as $organization) {
    [$provider, $profile] = entraFixture(organization: $organization);
    entraAssert(!$provider->canMatchEmail($profile), 'Missing, foreign, malformed, redirected, or unavailable organization must fail closed.');
}

[$provider, $profile] = entraFixture(['tenant' => 'EXAMPLE.ONMICROSOFT.COM', 'trustedEmailDomains' => "EXAMPLE.COM, example.org\nexample.com"]);
entraAssert($provider->canMatchEmail($profile), 'A verified tenant domain and case-normalized domain list should work.');
entraAssert($provider->validate(), 'Valid trust settings should validate.');
entraAssert(in_array('trustTenantEmail', $provider->settingsAttributes(), true) && in_array('trustedEmailDomains', $provider->settingsAttributes(), true), 'Trust settings must be persisted.');
entraAssert(!str_contains(json_encode($provider->getSettings()), 'fixture-access-token'), 'Private tokens and matching evidence must not be persisted.');
$profile->data['emailVerified'] = false;
entraAssert(!$provider->canMatchEmail($profile), 'An explicit unverified result must not be overridden.');

[$provider, $profile, $history] = entraFixture();
$provider->canMatchEmail($profile);
$provider->getOAuthProvider()->setHttpClient(new Client(['handler' => new MockHandler([entraResponse($GLOBALS['entraMember']), new Response(403)])]));
$token = new Token();
$token->setToken(new AccessToken(['access_token' => 'second-login-token']));
$secondProfile = $provider->getUserProfile($token);
entraAssert(!$provider->canMatchEmail($secondProfile) && !$provider->canMatchEmail($profile), 'A later login must not reuse an earlier successful trust decision.');

$google = new Google();
$unknownProfile = new UserProfile(new MicrosoftEntraResourceOwner($GLOBALS['entraMember']));
entraAssert(!$google->canMatchEmail($unknownProfile), 'Other providers must still reject unknown verification.');
$unknownProfile->data['emailVerified'] = true;
entraAssert($google->canMatchEmail($unknownProfile), 'Other providers must retain verified email matching.');
entraAssert(!(new MicrosoftEntra())->trustTenantEmail, 'Tenant trust must be disabled by default.');
entraAssert(!str_contains(json_encode(Craft::getLogger()->messages), 'fixture-token-must-not-leak'), 'Failure details must not expose token material.');

echo 'Entra tenant email security fixture passed (' . $GLOBALS['entraChecks'] . " checks).\n";
