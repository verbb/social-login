<?php

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\base\Provider;
use verbb\sociallogin\helpers\AssetHelper;
use verbb\sociallogin\models\UserField;
use verbb\sociallogin\services\Users;

use craft\elements\User;
use craft\helpers\FileHelper;

use yii\base\Component;
use yii\log\Logger;

use verbb\auth\models\UserProfile;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;

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

final class SensitiveLogFixtureProvider extends Provider
{
    public static string $handle = 'fixture';

    public static function displayName(): string
    {
        return 'Fixture';
    }

    public function getCraftUserFields(): array
    {
        return [new UserField([
            'handle' => 'unknownProperty',
        ])];
    }
}

final class SensitiveLogFixtureUser extends User
{
    public function __set($name, $value)
    {
        throw new RuntimeException("EXCEPTION_MESSAGE_SECRET: $value");
    }
}

final class SensitiveLogFixturePaths
{
    public function __construct(private string $tempPath)
    {
    }

    public function getTempPath(): string
    {
        return $this->tempPath;
    }
}

final class SensitiveLogFixtureGeneralConfig
{
    public int $cooldownDuration = 0;
    public int $defaultDirMode = 0775;
    public ?string $httpProxy = null;
    public bool $useEmailAsUsername = false;
}

final class SensitiveLogFixtureConfig
{
    public array $guzzleOptions = [];
    public MockHandler $handler;

    public function getConfigFromFile(string $filename): array
    {
        if ($filename === 'guzzle') {
            return ['handler' => $this->handler, ...$this->guzzleOptions];
        }

        if ($filename === 'social-login') {
            return ['trustedRemoteImageHosts' => ['example.test']];
        }

        return [];
    }

    public function getGeneral(): SensitiveLogFixtureGeneralConfig
    {
        return new SensitiveLogFixtureGeneralConfig();
    }
}

final class SensitiveLogFixtureSecurity
{
    private int $sequence = 0;

    public function generateRandomString(int $length): string
    {
        return str_pad((string)++$this->sequence, $length, '0', STR_PAD_LEFT);
    }
}

final class SensitiveLogFixtureSession
{
    public function get(string $key): mixed
    {
        return null;
    }
}

final class SensitiveLogFixtureI18n
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

final class SensitiveLogFixtureApp extends Component
{
    public array $loadedModules = [];
    public string $language = 'en';

    public function __construct(
        private SensitiveLogFixturePaths $paths,
        private SensitiveLogFixtureConfig $config,
        private SensitiveLogFixtureSecurity $security,
        private SensitiveLogFixtureSession $session,
    ) {
        parent::__construct();
    }

    public function getConfig(): SensitiveLogFixtureConfig
    {
        return $this->config;
    }

    public function getBasePath(): string
    {
        return dirname((new ReflectionClass(Craft::class))->getFileName());
    }

    public function getI18n(): SensitiveLogFixtureI18n
    {
        return new SensitiveLogFixtureI18n();
    }

    public function getIsInstalled(): bool
    {
        return false;
    }

    public function getPath(): SensitiveLogFixturePaths
    {
        return $this->paths;
    }

    public function getSecurity(): SensitiveLogFixtureSecurity
    {
        return $this->security;
    }

    public function getSession(): SensitiveLogFixtureSession
    {
        return $this->session;
    }

    public function getVersion(): string
    {
        return '5.0.0';
    }

    public function requireEdition($edition, bool $orBetter = true): void
    {
    }
}

function sensitiveLogFixtureAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function sensitiveLogFixtureProfile(array $data): UserProfile
{
    $profile = (new ReflectionClass(UserProfile::class))->newInstanceWithoutConstructor();
    $profile->data = $data;

    return $profile;
}

function sensitiveLogFixtureMessages(): string
{
    return implode("\n", array_map(static fn(array $message): string => (string)$message[0], Craft::getLogger()->messages));
}

$fixtureRoot = sys_get_temp_dir() . '/social-login-sensitive-log-security-' . bin2hex(random_bytes(8));
$tempRoot = $fixtureRoot . '/storage/runtime/temp';
$config = new SensitiveLogFixtureConfig();
$app = new SensitiveLogFixtureApp(
    new SensitiveLogFixturePaths($tempRoot),
    $config,
    new SensitiveLogFixtureSecurity(),
    new SensitiveLogFixtureSession(),
);
$plugin = (new ReflectionClass(SocialLogin::class))->newInstanceWithoutConstructor();
$plugin->id = 'social-login';
$plugin->setSettings([
    'enableRegistration' => true,
    'populateProfile' => false,
]);
$app->loadedModules[SocialLogin::class] = $plugin;
Craft::$app = $app;
Craft::setLogger(new Logger());
SocialLogin::$plugin = $plugin;

$users = new Users();
$provider = new SensitiveLogFixtureProvider([
    'fieldMapping' => [],
    'matchUserSource' => 'email',
]);
$missingEmailProfile = sensitiveLogFixtureProfile([
    'email' => null,
    'response' => [
        'access_token' => 'PROFILE_RESPONSE_SECRET',
    ],
]);
$getOrCreateUser = new ReflectionMethod(Users::class, '_getOrCreateUser');
$getOrCreateUser->invoke($users, $provider, $missingEmailProfile);
$missingEmailLog = sensitiveLogFixtureMessages();

sensitiveLogFixtureAssert(str_contains($missingEmailLog, 'fixture'), 'Missing-email logs must retain the provider handle.');
sensitiveLogFixtureAssert(!str_contains($missingEmailLog, 'PROFILE_RESPONSE_SECRET'), 'Missing-email logs must not contain the complete provider response.');

Craft::setLogger(new Logger());
$provider->fieldMapping = [
    'unknownProperty' => 'sensitiveValue',
];
$mappingProfile = sensitiveLogFixtureProfile([
    'sensitiveValue' => 'MAPPED_VALUE_SECRET',
]);
$syncUserProfile = new ReflectionMethod(Users::class, '_syncUserProfile');
$syncUserProfile->invoke($users, $provider, (new ReflectionClass(SensitiveLogFixtureUser::class))->newInstanceWithoutConstructor(), $mappingProfile);
$mappingLog = sensitiveLogFixtureMessages();

sensitiveLogFixtureAssert(str_contains($mappingLog, 'unknownProperty:sensitiveValue'), 'Mapping logs must retain the configured field and profile handles.');
sensitiveLogFixtureAssert(str_contains($mappingLog, 'fixture'), 'Mapping logs must retain the provider handle.');
sensitiveLogFixtureAssert(str_contains($mappingLog, RuntimeException::class), 'Mapping logs must retain the exception type.');
sensitiveLogFixtureAssert(!str_contains($mappingLog, 'MAPPED_VALUE_SECRET'), 'Mapping logs must not contain the provider value.');
sensitiveLogFixtureAssert(!str_contains($mappingLog, 'EXCEPTION_MESSAGE_SECRET'), 'Mapping logs must not contain the exception message.');

Craft::setLogger(new Logger());
$config->guzzleOptions = [
    RequestOptions::CURL => [
        CURLOPT_HEADERFUNCTION => static function(): int {
            return 0;
        },
    ],
];
$config->handler = new MockHandler([
    new RequestException(
        'IMAGE_EXCEPTION_SECRET',
        new Request('GET', 'https://example.test/private/IMAGE_PATH_SECRET?token=IMAGE_QUERY_SECRET'),
    ),
]);
$user = (new ReflectionClass(User::class))->newInstanceWithoutConstructor();
$user->email = 'IMAGE_EMAIL_SECRET@example.test';
\verbb\sociallogin\helpers\RemoteImageUrl::setResolver(fn(string $host): array => ['10.0.0.10']);

try {
    $result = AssetHelper::fetchRemoteImage(
        $user,
        'https://example.test/private/IMAGE_PATH_SECRET?token=IMAGE_QUERY_SECRET#IMAGE_FRAGMENT_SECRET',
        'user-photo',
    );
    $imageLog = sensitiveLogFixtureMessages();

    sensitiveLogFixtureAssert($result === null, 'Failed image downloads must retain null behavior.');
    sensitiveLogFixtureAssert(str_contains($imageLog, 'https://example.test'), 'Image logs must retain the normalized remote origin.');
    sensitiveLogFixtureAssert(str_contains($imageLog, RequestException::class), 'Image logs must retain the exception type.');
    sensitiveLogFixtureAssert(!str_contains($imageLog, 'IMAGE_EMAIL_SECRET'), 'Image logs must not contain the user email.');
    sensitiveLogFixtureAssert(!str_contains($imageLog, 'IMAGE_PATH_SECRET'), 'Image logs must not contain URL paths.');
    sensitiveLogFixtureAssert(!str_contains($imageLog, 'IMAGE_QUERY_SECRET'), 'Image logs must not contain URL queries.');
    sensitiveLogFixtureAssert(!str_contains($imageLog, 'IMAGE_FRAGMENT_SECRET'), 'Image logs must not contain URL fragments.');
    sensitiveLogFixtureAssert(!str_contains($imageLog, 'IMAGE_EXCEPTION_SECRET'), 'Image logs must not contain the exception message.');
    sensitiveLogFixtureAssert(!is_dir($fixtureRoot) || FileHelper::findFiles($fixtureRoot) === [], 'Failed image downloads must retain temporary-file cleanup.');

    Craft::setLogger(new Logger());
    $userInfoResult = AssetHelper::fetchRemoteImage(
        $user,
        'https://USERINFO_SECRET:USERINFO_PASSWORD@example.test/private/USERINFO_PATH_SECRET?token=USERINFO_QUERY_SECRET',
        'user-info-photo',
    );
    $userInfoLog = sensitiveLogFixtureMessages();

    sensitiveLogFixtureAssert($userInfoResult === null, 'Credential-bearing image URLs must retain rejection behavior.');
    sensitiveLogFixtureAssert(str_contains($userInfoLog, 'https://example.test'), 'Rejected image URLs must retain the normalized remote origin.');
    sensitiveLogFixtureAssert(!str_contains($userInfoLog, 'USERINFO_SECRET'), 'Image logs must not contain URL user information.');
    sensitiveLogFixtureAssert(!str_contains($userInfoLog, 'USERINFO_PASSWORD'), 'Image logs must not contain URL passwords.');
    sensitiveLogFixtureAssert(!str_contains($userInfoLog, 'USERINFO_PATH_SECRET'), 'Image logs must not contain rejected URL paths.');
    sensitiveLogFixtureAssert(!str_contains($userInfoLog, 'USERINFO_QUERY_SECRET'), 'Image logs must not contain rejected URL queries.');
} finally {
    \verbb\sociallogin\helpers\RemoteImageUrl::setResolver(null);

    if (is_dir($fixtureRoot)) {
        FileHelper::removeDirectory($fixtureRoot);
    }
}

echo "Sensitive log security fixture passed.\n";
