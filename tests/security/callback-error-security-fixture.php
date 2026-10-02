<?php

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\base\Provider;
use verbb\sociallogin\controllers\AuthController;
use verbb\sociallogin\providers\GitHub;
use verbb\sociallogin\services\Providers;

use yii\base\Component;
use yii\log\Logger;
use yii\web\Response as YiiResponse;

use verbb\auth\Auth;
use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\auth\services\OAuth;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

$vendorPath = getenv('VERBB_SOCIAL_LOGIN_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';

if (!is_file($vendorPath . '/autoload.php')) {
    throw new RuntimeException('Set VERBB_SOCIAL_LOGIN_TEST_VENDOR to a Craft 5 vendor directory.');
}

require $vendorPath . '/autoload.php';
require $vendorPath . '/yiisoft/yii2/Yii.php';
require $vendorPath . '/craftcms/cms/src/Craft.php';

spl_autoload_register(static function(string $class): void {
    $prefix = 'verbb\\sociallogin\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = dirname(__DIR__, 2) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (is_file($path)) {
        require $path;
    }
}, true, true);

final class CallbackErrorFixtureRequest
{
    public function getIsCpRequest(): bool
    {
        return false;
    }
}

final class CallbackErrorFixtureSession
{
    public array $flashes = [];
    public array $values = [
        'verbb-auth.origin' => 'https://example.test/login',
        'verbb-auth.redirect' => 'https://example.test/account',
    ];

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    public function setFlash(string $key, mixed $value, bool $removeAfterAccess = true): void
    {
        $this->flashes[$key] = $value;
    }
}

final class CallbackErrorFixtureSecurity
{
    public function generateRandomString(int $length = 32): string
    {
        return 'SUPPORTREF123456';
    }
}

final class CallbackErrorFixtureI18n
{
    public function translate(string $category, string $message, array $params = [], ?string $language = null): string
    {
        $replacements = [];

        foreach ($params as $key => $value) {
            $replacements["{{$key}}"] = $value;
        }

        return strtr($message, $replacements);
    }
}

final class CallbackErrorFixtureProvider extends GitHub
{
    public function canLogin(bool $isCpRequest): bool
    {
        return true;
    }
}

final class CallbackErrorFixtureProviders extends Providers
{
    public function __construct(private Provider $provider)
    {
    }

    public function getProviderByHandle(string $handle): ?Provider
    {
        return $handle === 'gitHub' ? $this->provider : null;
    }
}

final class CallbackErrorFixtureOAuth extends OAuth
{
    public function __construct(private RequestException $exception)
    {
    }

    public function prepareCallback(?string $ownerHandle = null): ?YiiResponse
    {
        return null;
    }

    public function claimCallback(?string $ownerHandle = null, ?string $transactionId = null): array
    {
        return [
            'context' => [
                'providerHandle' => 'gitHub',
                'isConnect' => false,
                'isCpRequest' => false,
            ],
            'initiatingUserId' => null,
        ];
    }

    public function callback(string $ownerHandle, OAuthProviderInterface $provider, string|int|null $reference = null): Token
    {
        throw $this->exception;
    }
}

final class CallbackErrorFixtureAuth extends Auth
{
    public function __construct(private OAuth $oauth)
    {
    }

    public function getOAuth(): OAuth
    {
        return $this->oauth;
    }
}

final class CallbackErrorFixtureApp extends Component
{
    public array $loadedModules = [];
    public string $charset = 'UTF-8';
    public string $language = 'en';

    public function __construct(
        private CallbackErrorFixtureSession $session,
        private CallbackErrorFixtureSecurity $security,
        private CallbackErrorFixtureAuth $auth,
    ) {
        parent::__construct();
    }

    public function getI18n(): CallbackErrorFixtureI18n
    {
        return new CallbackErrorFixtureI18n();
    }

    public function getModule(string $id, bool $load = true): mixed
    {
        return $id === Auth::ID ? $this->auth : null;
    }

    public function getRequest(): CallbackErrorFixtureRequest
    {
        return new CallbackErrorFixtureRequest();
    }

    public function getSecurity(): CallbackErrorFixtureSecurity
    {
        return $this->security;
    }

    public function getSession(): CallbackErrorFixtureSession
    {
        return $this->session;
    }
}

final class CallbackErrorFixtureController extends AuthController
{
    public function redirect($url, $statusCode = 302): YiiResponse
    {
        $response = new YiiResponse();
        $response->setStatusCode($statusCode);
        $response->getHeaders()->set('Location', (string)$url);

        return $response;
    }
}

function callbackErrorFixtureAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$request = new Request('GET', 'https://provider.test/token?client_secret=QUERY_SECRET');
$providerResponse = new Response(500, ['Content-Type' => 'text/plain'], 'PROVIDER_RESPONSE_SECRET');
$exception = RequestException::create($request, $providerResponse);
$session = new CallbackErrorFixtureSession();
$oauth = new CallbackErrorFixtureOAuth($exception);
$app = new CallbackErrorFixtureApp($session, new CallbackErrorFixtureSecurity(), new CallbackErrorFixtureAuth($oauth));
$provider = new CallbackErrorFixtureProvider();
$providers = new CallbackErrorFixtureProviders($provider);
$plugin = (new ReflectionClass(SocialLogin::class))->newInstanceWithoutConstructor();
$plugin->id = 'social-login';
$plugin->set('providers', $providers);
$app->loadedModules[SocialLogin::class] = $plugin;
Craft::$app = $app;
Craft::setLogger(new Logger());
SocialLogin::$plugin = $plugin;

$controller = (new ReflectionClass(CallbackErrorFixtureController::class))->newInstanceWithoutConstructor();
$response = $controller->actionCallback();
$publicError = $session->flashes['social-login:error'] ?? null;
$logMessage = Craft::getLogger()->messages[0][0] ?? null;

callbackErrorFixtureAssert($response->getStatusCode() === 302, 'Callback failures must retain redirect behavior.');
callbackErrorFixtureAssert($response->getHeaders()->get('Location') === 'https://example.test/login', 'Callback failures must return to the initiating origin.');
callbackErrorFixtureAssert(is_string($publicError), 'Callback failures must retain a public error flash.');
callbackErrorFixtureAssert($publicError === 'Unable to process the social login request. Reference: SUPPORTREF123456.', 'Callback failures must expose only the stable message and support reference.');
callbackErrorFixtureAssert(!str_contains($publicError, $exception->getMessage()), 'Callback failures must not expose raw exception messages.');
callbackErrorFixtureAssert(!str_contains($publicError, 'QUERY_SECRET'), 'Callback failures must not expose credential-bearing request URLs.');
callbackErrorFixtureAssert(!str_contains($publicError, 'PROVIDER_RESPONSE_SECRET'), 'Callback failures must not expose provider response content.');
callbackErrorFixtureAssert(is_string($logMessage) && str_contains($logMessage, 'SUPPORTREF123456'), 'Server logs must contain the public support reference.');
callbackErrorFixtureAssert(str_contains($logMessage, $exception->getMessage()), 'Server logs must retain the exception diagnostic.');
callbackErrorFixtureAssert(str_contains($logMessage, 'gitHub'), 'Server logs must retain the provider diagnostic.');

$translations = require dirname(__DIR__, 2) . '/src/translations/en/social-login.php';

callbackErrorFixtureAssert(isset($translations['Unable to process the social login request. Reference: {reference}.']), 'The stable public callback error must be translatable.');
callbackErrorFixtureAssert(!isset($translations['Unable to process callback for “{provider}”: “{message}”']), 'The exception-bearing public translation must be removed.');

echo "Callback error security fixture passed.\n";
