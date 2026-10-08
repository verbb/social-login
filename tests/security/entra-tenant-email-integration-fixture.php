<?php

// Run only against a disposable/development Craft site. All fixture database changes are rolled back.
use craft\elements\User;
use craft\web\View;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Token\AccessToken;
use verbb\auth\models\Token;
use verbb\sociallogin\models\Connection;
use verbb\sociallogin\providers\MicrosoftEntra;
use verbb\sociallogin\services\Connections;
use verbb\sociallogin\SocialLogin;

$appPath = getenv('VERBB_SOCIAL_LOGIN_TEST_APP');

if (!$appPath || !is_file($appPath . '/bootstrap.php')) {
    throw new RuntimeException('Set VERBB_SOCIAL_LOGIN_TEST_APP to a development Craft 5 application.');
}

require $appPath . '/bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
$plugin = SocialLogin::$plugin;
$originalConnections = $plugin->getConnections();
$transaction = $app->getDb()->beginTransaction();
$checks = 0;

function entraIntegrationAssert(bool $condition, string $description): void
{
    if (!$condition) {
        throw new RuntimeException($description);
    }

    $GLOBALS['checks']++;
}

final class EntraEmailIntegrationConnections extends Connections
{
    public ?Connection $existing = null;

    public function getConnectionByUserAndProvider(int $userId, string $providerHandle): ?Connection
    {
        return $this->existing;
    }
}

final class EntraEmailIntegrationProvider extends MicrosoftEntra
{
    public function getRedirectUri(): ?string
    {
        return 'https://craft.example.test/callback';
    }
}

try {
    $suffix = bin2hex(random_bytes(6));
    $email = 'entra-fixture-' . $suffix . '@example.com';
    $user = new User(['username' => $email, 'email' => $email]);
    entraIntegrationAssert($app->getElements()->saveElement($user), 'Fixture user should save: ' . json_encode($user->getErrors()));
    $connections = new EntraEmailIntegrationConnections();
    $plugin->set('connections', $connections);

    $provider = new EntraEmailIntegrationProvider([
        'tenant' => '11111111-2222-3333-4444-555555555555',
        'trustTenantEmail' => true,
        'trustedEmailDomains' => 'example.com',
    ]);
    $member = [
        'id' => 'entra-object-' . $suffix,
        'mail' => $email,
        'userPrincipalName' => $email,
        'userType' => 'Member',
        'externalUserState' => null,
    ];
    $organization = ['value' => [[
        'id' => $provider->tenant,
        'verifiedDomains' => [['name' => 'example.com']],
    ]]];
    $provider->getOAuthProvider()->setHttpClient(new Client(['handler' => new MockHandler([
        new Response(200, ['Content-Type' => 'application/json'], json_encode($member)),
        new Response(200, ['Content-Type' => 'application/json'], json_encode($member)),
        new Response(200, ['Content-Type' => 'application/json'], json_encode($organization)),
    ])]));
    $token = new Token();
    $token->setToken(new AccessToken(['access_token' => 'integration-fixture-token']));
    $profile = $provider->getUserProfile($token);
    $users = $plugin->getUsers();
    $match = new ReflectionMethod($users, '_matchExistingUser');
    $matched = $match->invoke($users, $provider, $profile);
    entraIntegrationAssert($matched?->id === $user->id, 'The actual Users service must match the intended Craft user.');
    entraIntegrationAssert($profile->getEmailVerified() === null, 'Matching must not alter verification status.');

    $app->getDb()->createCommand()->update('{{%users}}', ['admin' => true], ['id' => $user->id])->execute();
    entraIntegrationAssert($match->invoke($users, $provider, $profile) === null, 'The actual query/matching flow must reject an administrator.');
    $app->getDb()->createCommand()->update('{{%users}}', ['admin' => false], ['id' => $user->id])->execute();
    $connections->existing = new Connection(['identifier' => 'older-identity']);
    entraIntegrationAssert($match->invoke($users, $provider, $profile) === null, 'The actual matching flow must preserve an existing identity.');
    $connections->existing = null;
    $provider->trustTenantEmail = false;
    entraIntegrationAssert($match->invoke($users, $provider, $profile) === null, 'Disabling trust must restore the default verified-email requirement.');
    $provider->trustTenantEmail = true;
    $provider->matchUserDestination = 'username';
    entraIntegrationAssert($match->invoke($users, $provider, $profile) === null, 'The service must not apply the exception to username matching.');
    $provider->matchUserDestination = 'email';

    // The exception must not leak into the separate email-profile-sync decision.
    $provider->fieldMapping = ['email' => 'email'];
    $user->email = 'preserved-' . $suffix . '@example.com';
    (new ReflectionMethod($users, '_syncUserProfile'))->invoke($users, $provider, $user, $profile);
    entraIntegrationAssert($user->email === 'preserved-' . $suffix . '@example.com', 'Unverified email must not be synchronized after a trusted match.');

    $view = $app->getView();
    $view->setTemplateMode(View::TEMPLATE_MODE_CP);
    $html = $provider->getSettingsHtml();
    // Craft's autosuggest input is created by Vue from registered initialization data.
    $js = implode("\n", array_merge(...array_values($view->js)));
    entraIntegrationAssert(str_contains($html, 'name="trustTenantEmail"') && str_contains($js, '"name":"trustedEmailDomains"'), 'Craft must render both new settings with their expected input names.');
    $provider->tenant = 'common';
    $provider->validate(['tenant']);
    $html = $provider->getSettingsHtml();
    entraIntegrationAssert(str_contains($html, 'Trusted email matching requires a specific organisation tenant ID or verified domain.'), 'Craft must render the tenant validation error.');

    echo "Entra tenant email integration fixture passed ($checks checks).\n";
} finally {
    $plugin->set('connections', $originalConnections);
    $transaction->rollBack();
}
