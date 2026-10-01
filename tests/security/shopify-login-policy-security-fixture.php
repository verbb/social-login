<?php

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\controllers\AuthController;
use verbb\sociallogin\providers\Shopify;
use verbb\sociallogin\services\Providers;
use verbb\sociallogin\services\Service;
use verbb\sociallogin\services\Users;

use verbb\auth\models\Token;

$vendorPath = getenv('VERBB_SOCIAL_LOGIN_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';

if (!is_file($vendorPath . '/autoload.php')) {
    throw new RuntimeException('Set VERBB_SOCIAL_LOGIN_TEST_VENDOR to a Craft 5 vendor directory.');
}

require $vendorPath . '/autoload.php';
require $vendorPath . '/yiisoft/yii2/Yii.php';
require $vendorPath . '/craftcms/cms/src/Craft.php';
require dirname(__DIR__, 2) . '/src/base/ProviderInterface.php';
require dirname(__DIR__, 2) . '/src/base/Provider.php';
require dirname(__DIR__, 2) . '/src/base/OAuthProvider.php';
require dirname(__DIR__, 2) . '/src/base/PluginTrait.php';
require dirname(__DIR__, 2) . '/src/models/Settings.php';
require dirname(__DIR__, 2) . '/src/providers/Shopify.php';
require dirname(__DIR__, 2) . '/src/services/Providers.php';
require dirname(__DIR__, 2) . '/src/services/Service.php';
require dirname(__DIR__, 2) . '/src/services/Users.php';
require dirname(__DIR__, 2) . '/src/SocialLogin.php';
require dirname(__DIR__, 2) . '/src/controllers/AuthController.php';

final class ShopifyLoginPolicyFixtureRequest
{
    public function getIsCpRequest(): bool
    {
        return false;
    }
}

final class ShopifyLoginPolicyFixtureApp extends yii\base\Component
{
    public ShopifyLoginPolicyFixtureRequest $request;

    public function getRequest(): ShopifyLoginPolicyFixtureRequest
    {
        return $this->request;
    }
}

final class ShopifyLoginPolicyFixtureProviders extends Providers
{
    public array $fixtureProviders = [];

    public function getAllProviders(): array
    {
        return $this->fixtureProviders;
    }

    public function getProviderByHandle(string $handle): ?verbb\sociallogin\base\Provider
    {
        foreach ($this->fixtureProviders as $provider) {
            if ($provider->handle === $handle) {
                return $provider;
            }
        }

        return null;
    }
}

function shopifyLoginPolicyFixtureAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$provider = new Shopify([
    'enabled' => true,
    'loginEnabled' => true,
    'cpLoginEnabled' => true,
]);

$providers = new ShopifyLoginPolicyFixtureProviders();
$providers->fixtureProviders = [$provider];

$plugin = (new ReflectionClass(SocialLogin::class))->newInstanceWithoutConstructor();
$plugin->set('providers', $providers);
(new ReflectionProperty(craft\base\Plugin::class, '_settings'))->setValue($plugin, new verbb\sociallogin\models\Settings());
SocialLogin::$plugin = $plugin;

$app = new ShopifyLoginPolicyFixtureApp();
$app->request = new ShopifyLoginPolicyFixtureRequest();
Craft::$app = $app;

shopifyLoginPolicyFixtureAssert(!Shopify::supportsLogin(), 'Shopify must not be available as a Craft login identity.');
shopifyLoginPolicyFixtureAssert(!$provider->canLogin(false), 'Shopify site login must remain disabled when its login setting is enabled.');
shopifyLoginPolicyFixtureAssert(!$provider->canLogin(true), 'Shopify control panel login must remain disabled when its login setting is enabled.');
shopifyLoginPolicyFixtureAssert($providers->getAllLoginProviders() === [], 'Shopify must be excluded from site login provider lists.');
shopifyLoginPolicyFixtureAssert($providers->getAllCpLoginProviders() === [], 'Shopify must be excluded from control panel login provider lists.');

$service = new Service();
shopifyLoginPolicyFixtureAssert($service->getLoginUrl('shopify') === null, 'Shopify login URLs must not be generated.');

$users = new Users();
shopifyLoginPolicyFixtureAssert(
    !$users->loginOrRegisterUser($provider, new Token()),
    'The public user service must reject Shopify authentication.',
);

$controller = (new ReflectionClass(AuthController::class))->newInstanceWithoutConstructor();
$authorizationAllowed = new ReflectionMethod($controller, '_isAuthorizationAllowed');

shopifyLoginPolicyFixtureAssert(
    !$authorizationAllowed->invoke($controller, $provider, false, false, null),
    'Direct Shopify site login authorization must be rejected.',
);
shopifyLoginPolicyFixtureAssert(
    !$authorizationAllowed->invoke($controller, $provider, false, true, null),
    'Direct Shopify control panel login authorization must be rejected.',
);
shopifyLoginPolicyFixtureAssert(
    $authorizationAllowed->invoke($controller, $provider, true, false, 123),
    'An enabled Shopify provider must remain available for signed-in account connections.',
);
shopifyLoginPolicyFixtureAssert(
    !$authorizationAllowed->invoke($controller, $provider, true, false, null),
    'A Shopify connection must still require an initiating Craft user.',
);

echo "Shopify login policy security fixture passed.\n";
