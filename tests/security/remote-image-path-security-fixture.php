<?php

use verbb\sociallogin\helpers\AssetHelper;

use craft\elements\User;
use craft\helpers\FileHelper;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;

$vendorPath = getenv('VERBB_SOCIAL_LOGIN_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';

if (!is_file($vendorPath . '/autoload.php')) {
    throw new RuntimeException('Set VERBB_SOCIAL_LOGIN_TEST_VENDOR to a Craft 5 vendor directory.');
}

require $vendorPath . '/autoload.php';
require $vendorPath . '/yiisoft/yii2/Yii.php';
require $vendorPath . '/craftcms/cms/src/Craft.php';
require dirname(__DIR__, 2) . '/src/base/PluginTrait.php';
require dirname(__DIR__, 2) . '/src/models/Settings.php';
require dirname(__DIR__, 2) . '/src/SocialLogin.php';
require dirname(__DIR__, 2) . '/src/helpers/RemoteImageUrl.php';
require dirname(__DIR__, 2) . '/src/helpers/AssetHelper.php';

final class RemoteImagePathFixturePaths
{
    public function __construct(private string $tempPath)
    {
    }

    public function getTempPath(): string
    {
        return $this->tempPath;
    }
}

final class RemoteImagePathFixtureConfig
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

    public function getGeneral(): object
    {
        return (object)[
            'defaultDirMode' => 0775,
            'httpProxy' => null,
        ];
    }
}

final class RemoteImagePathFixtureSecurity
{
    private int $sequence = 0;

    public function generateRandomString(int $length): string
    {
        return str_pad((string)++$this->sequence, $length, '0', STR_PAD_LEFT);
    }
}

final class RemoteImagePathFixtureApp extends yii\base\Component
{
    public function __construct(
        private RemoteImagePathFixturePaths $paths,
        private RemoteImagePathFixtureConfig $config,
        private RemoteImagePathFixtureSecurity $security,
    ) {
        parent::__construct();
    }

    public function getPath(): RemoteImagePathFixturePaths
    {
        return $this->paths;
    }

    public function getConfig(): RemoteImagePathFixtureConfig
    {
        return $this->config;
    }

    public function getSecurity(): RemoteImagePathFixtureSecurity
    {
        return $this->security;
    }

    public function getVersion(): string
    {
        return '5.0.0';
    }
}

function remoteImagePathFixtureAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function remoteImagePathFixtureUser(?string $email): User
{
    $user = (new ReflectionClass(User::class))->newInstanceWithoutConstructor();
    $user->email = $email;

    return $user;
}

$fixtureRoot = sys_get_temp_dir() . '/social-login-path-security-' . bin2hex(random_bytes(8));
$tempRoot = $fixtureRoot . '/storage/runtime/temp';
$config = new RemoteImagePathFixtureConfig();
Craft::$app = new RemoteImagePathFixtureApp(
    new RemoteImagePathFixturePaths($tempRoot),
    $config,
    new RemoteImagePathFixtureSecurity(),
);
$createTempPath = new ReflectionMethod(AssetHelper::class, '_createTempPath');
$socialLoginRoot = FileHelper::normalizePath($tempRoot . '/social-login');
\verbb\sociallogin\helpers\RemoteImageUrl::setResolver(fn(string $host): array => ['10.0.0.10']);

try {
    $emails = [
        '../../../../outside/disposable',
        '..\\..\\..\\..\\outside\\disposable',
        '.',
        '..',
        "disposable\0identity@example.test",
        null,
        'person@example.test',
    ];

    foreach ($emails as $email) {
        $path = FileHelper::normalizePath($createTempPath->invoke(null, remoteImagePathFixtureUser($email)));
        $relativePath = substr($path, strlen($socialLoginRoot) + 1);
        $segments = explode('/', trim($relativePath, '/'));

        remoteImagePathFixtureAssert(str_starts_with($path, $socialLoginRoot . '/'), 'Temporary paths must remain under the Social Login root.');
        remoteImagePathFixtureAssert(count($segments) === 2, 'Temporary paths must contain only identity and request segments.');
        remoteImagePathFixtureAssert((bool)preg_match('/^[a-f0-9]{64}$/', $segments[0]), 'Identity path segments must be fixed-format hexadecimal values.');
        remoteImagePathFixtureAssert((bool)preg_match('/^[0-9]{32}$/', $segments[1]), 'Request path segments must be server-generated fixed-format values.');
    }

    $ordinaryUser = remoteImagePathFixtureUser('person@example.test');
    $firstPath = FileHelper::normalizePath($createTempPath->invoke(null, $ordinaryUser));
    $secondPath = FileHelper::normalizePath($createTempPath->invoke(null, $ordinaryUser));

    remoteImagePathFixtureAssert(dirname(rtrim($firstPath, '/')) === dirname(rtrim($secondPath, '/')), 'The same identity must retain its derived namespace.');
    remoteImagePathFixtureAssert($firstPath !== $secondPath, 'Each download must receive an isolated request directory.');

    foreach (['nested/name', 'nested\\name', '.', '..', ''] as $filename) {
        $result = AssetHelper::fetchRemoteImage($ordinaryUser, 'https://example.test/photo', $filename);

        remoteImagePathFixtureAssert($result === null, 'Temporary filenames must be a single filename component.');
        remoteImagePathFixtureAssert(FileHelper::findFiles($socialLoginRoot) === [], 'Rejected temporary filenames must not create files.');
    }

    $config->handler = new MockHandler([
        new Response(200, ['Content-Type' => 'text/plain'], 'not an image'),
    ]);

    $result = AssetHelper::fetchRemoteImage($ordinaryUser, 'https://example.test/photo', 'user-photo');

    remoteImagePathFixtureAssert($result === null, 'Unsupported content must be rejected.');
    remoteImagePathFixtureAssert(FileHelper::findFiles($socialLoginRoot) === [], 'Rejected downloads must not leave extensionless files.');

    $config->handler = new MockHandler([
        new Response(200, ['Content-Type' => 'image/png'], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')),
    ]);

    $result = AssetHelper::fetchRemoteImage($ordinaryUser, 'https://example.test/photo', 'user-photo');

    remoteImagePathFixtureAssert(is_string($result) && is_file($result), 'Valid images must remain available to the caller.');
    remoteImagePathFixtureAssert(basename($result) === 'user-photo.png', 'Valid images must preserve their expected filename.');
    remoteImagePathFixtureAssert(str_starts_with(FileHelper::normalizePath($result), $socialLoginRoot . '/'), 'Valid images must remain under the Social Login root.');
    remoteImagePathFixtureAssert(!is_file(substr($result, 0, -4)), 'Successful downloads must not retain an extensionless file.');
    $defaultOptions = $config->handler->getLastOptions();
    remoteImagePathFixtureAssert(($defaultOptions[RequestOptions::CONNECT_TIMEOUT] ?? 0) > 0 && $defaultOptions[RequestOptions::CONNECT_TIMEOUT] <= 5, 'Remote images must use the plugin connection timeout ceiling.');
    remoteImagePathFixtureAssert(($defaultOptions[RequestOptions::TIMEOUT] ?? 0) > 0 && $defaultOptions[RequestOptions::TIMEOUT] <= 15, 'Remote images must use the plugin transfer timeout ceiling.');

    $configuredHeadersSeen = 0;
    $configuredProgress = static function(): void {
    };
    $configuredWrite = static function(): int {
        return 0;
    };
    $config->guzzleOptions = [
        RequestOptions::CONNECT_TIMEOUT => 4,
        RequestOptions::CURL => [
            CURLOPT_CONNECTTIMEOUT_MS => 3000,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 20,
            CURLOPT_NOSIGNAL => false,
            CURLOPT_POSTREDIR => CURL_REDIR_POST_ALL,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_ALL,
            CURLOPT_TCP_KEEPALIVE => 1,
            CURLOPT_TIMEOUT_MS => 7000,
            CURLOPT_WRITEFUNCTION => $configuredWrite,
        ],
        RequestOptions::ON_HEADERS => static function() use (&$configuredHeadersSeen): void {
            $configuredHeadersSeen++;
        },
        RequestOptions::PROGRESS => $configuredProgress,
        RequestOptions::TIMEOUT => 8,
    ];
    $config->handler = new MockHandler([
        new Response(200, ['Content-Type' => 'image/png'], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')),
    ]);

    $boundedResult = AssetHelper::fetchRemoteImage($ordinaryUser, 'https://example.test/photo', 'bounded-photo');
    $capturedOptions = $config->handler->getLastOptions();

    remoteImagePathFixtureAssert(is_string($boundedResult) && is_file($boundedResult), 'Valid images must remain compatible with configured request options.');
    remoteImagePathFixtureAssert(($capturedOptions[RequestOptions::CONNECT_TIMEOUT] ?? null) === 3.0, 'A stricter raw connection timeout must be converted to the canonical request option.');
    remoteImagePathFixtureAssert(($capturedOptions[RequestOptions::TIMEOUT] ?? null) === 7.0, 'A stricter raw transfer timeout must be converted to the canonical request option.');
    remoteImagePathFixtureAssert($configuredHeadersSeen === 1, 'Configured response-header callbacks must remain intact.');
    remoteImagePathFixtureAssert(($capturedOptions[RequestOptions::PROGRESS] ?? null) === $configuredProgress, 'Configured progress callbacks must remain intact.');
    remoteImagePathFixtureAssert(($capturedOptions[RequestOptions::CURL][CURLOPT_TCP_KEEPALIVE] ?? null) === 1, 'Unrelated configured cURL options must remain intact.');
    remoteImagePathFixtureAssert(!array_key_exists(CURLOPT_CONNECTTIMEOUT_MS, $capturedOptions[RequestOptions::CURL]), 'Raw connection timeout options must not override the canonical ceiling.');
    remoteImagePathFixtureAssert(!array_key_exists(CURLOPT_FOLLOWLOCATION, $capturedOptions[RequestOptions::CURL]), 'Raw redirect handling must not bypass validated manual redirects.');
    remoteImagePathFixtureAssert(!array_key_exists(CURLOPT_MAXREDIRS, $capturedOptions[RequestOptions::CURL]), 'Raw redirect limits must not override the plugin redirect policy.');
    remoteImagePathFixtureAssert(!array_key_exists(CURLOPT_NOSIGNAL, $capturedOptions[RequestOptions::CURL]), 'Raw signal handling must not conflict with canonical timeout options.');
    remoteImagePathFixtureAssert(!array_key_exists(CURLOPT_POSTREDIR, $capturedOptions[RequestOptions::CURL]), 'Raw redirect method controls must not bypass validated manual redirects.');
    remoteImagePathFixtureAssert(!array_key_exists(CURLOPT_REDIR_PROTOCOLS, $capturedOptions[RequestOptions::CURL]), 'Raw redirect protocol controls must not bypass validated manual redirects.');
    remoteImagePathFixtureAssert(!array_key_exists(CURLOPT_TIMEOUT_MS, $capturedOptions[RequestOptions::CURL]), 'Raw transfer timeout options must not override the canonical ceiling.');
    remoteImagePathFixtureAssert(!array_key_exists(CURLOPT_WRITEFUNCTION, $capturedOptions[RequestOptions::CURL]), 'Raw write callbacks must not bypass the bounded sink.');
    $config->guzzleOptions = [];

    $config->handler = new MockHandler([
        new Response(200, ['Content-Length' => (string)((10 * 1024 * 1024) + 1), 'Content-Type' => 'image/png'], 'small body'),
    ]);

    $filesBeforeOversizedHeader = count(FileHelper::findFiles($socialLoginRoot));
    $oversizedHeaderResult = AssetHelper::fetchRemoteImage($ordinaryUser, 'https://example.test/photo', 'oversized-header-photo');

    remoteImagePathFixtureAssert($oversizedHeaderResult === null, 'Oversized declared response bodies must be rejected before download.');
    remoteImagePathFixtureAssert(count(FileHelper::findFiles($socialLoginRoot)) === $filesBeforeOversizedHeader, 'Rejected declared response bodies must not leave extensionless files.');

    $maximumImage = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    $maximumImage .= str_repeat("\0", (10 * 1024 * 1024) - strlen($maximumImage));
    $config->handler = new MockHandler([
        new Response(200, ['Content-Type' => 'image/png'], $maximumImage),
    ]);

    $maximumResult = AssetHelper::fetchRemoteImage($ordinaryUser, 'https://example.test/photo', 'maximum-photo');

    remoteImagePathFixtureAssert(is_string($maximumResult) && is_file($maximumResult), 'Images exactly at the download limit must remain valid.');
    remoteImagePathFixtureAssert(filesize($maximumResult) === 10 * 1024 * 1024, 'The complete image at the download limit must be retained.');

    $oversizedImage = $maximumImage . "\0";
    $config->handler = new MockHandler([
        new Response(200, ['Content-Type' => 'image/png'], $oversizedImage),
    ]);

    $filesBeforeOversizedBody = count(FileHelper::findFiles($socialLoginRoot));
    $oversizedResult = AssetHelper::fetchRemoteImage($ordinaryUser, 'https://example.test/photo', 'oversized-photo');

    remoteImagePathFixtureAssert($oversizedResult === null, 'Images over the cumulative download limit must be rejected.');
    remoteImagePathFixtureAssert(count(FileHelper::findFiles($socialLoginRoot)) === $filesBeforeOversizedBody, 'Rejected oversized downloads must not leave extensionless files.');

    $sixMegabytes = 6 * 1024 * 1024;
    $fiveMegabytes = 5 * 1024 * 1024;
    $config->handler = new MockHandler([
        new Response(302, ['Location' => '/large-redirect'], str_repeat('a', $sixMegabytes)),
        new Response(200, ['Content-Type' => 'image/png'], str_repeat('b', $fiveMegabytes)),
    ]);

    $filesBeforeCumulativeBody = count(FileHelper::findFiles($socialLoginRoot));
    $cumulativeResult = AssetHelper::fetchRemoteImage($ordinaryUser, 'https://example.test/photo', 'cumulative-photo');

    remoteImagePathFixtureAssert($cumulativeResult === null, 'Redirect bodies and final responses must share one cumulative download limit.');
    remoteImagePathFixtureAssert(count(FileHelper::findFiles($socialLoginRoot)) === $filesBeforeCumulativeBody, 'Rejected cumulative downloads must not leave extensionless files.');

    $config->handler = new MockHandler([
        new Response(302, ['Location' => '/redirected-photo']),
        new Response(200, ['Content-Type' => 'image/png'], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')),
    ]);

    $redirectedResult = AssetHelper::fetchRemoteImage($ordinaryUser, 'http://example.test/photo', 'redirected-photo');

    remoteImagePathFixtureAssert(is_string($redirectedResult) && is_file($redirectedResult), 'Public HTTP images must continue through a permitted relative redirect.');

    $config->handler = new MockHandler([
        new Response(302, ['Location' => '/photo-1']),
        new Response(302, ['Location' => '/photo-2']),
        new Response(302, ['Location' => '/photo-3']),
        new Response(302, ['Location' => '/photo-4']),
        new Response(302, ['Location' => '/photo-5']),
        new Response(200, ['Content-Type' => 'image/png'], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')),
    ]);

    $fiveRedirectResult = AssetHelper::fetchRemoteImage($ordinaryUser, 'https://example.test/photo', 'five-redirect-photo');

    remoteImagePathFixtureAssert(is_string($fiveRedirectResult) && is_file($fiveRedirectResult), 'Five permitted redirects must remain compatible.');

    $config->handler = new MockHandler([
        new Response(302, ['Location' => '/photo-1']),
        new Response(302, ['Location' => '/photo-2']),
        new Response(302, ['Location' => '/photo-3']),
        new Response(302, ['Location' => '/photo-4']),
        new Response(302, ['Location' => '/photo-5']),
        new Response(302, ['Location' => '/photo-6']),
        new Response(200, ['Content-Type' => 'image/png'], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')),
    ]);

    $tooManyRedirectsResult = AssetHelper::fetchRemoteImage($ordinaryUser, 'https://example.test/photo', 'too-many-redirects-photo');

    remoteImagePathFixtureAssert($tooManyRedirectsResult === null, 'More than five redirects must be rejected.');
    remoteImagePathFixtureAssert(count($config->handler) === 1, 'The redirect limit must stop before the next destination is requested.');

    echo "Remote image path security fixture passed.\n";
} finally {
    \verbb\sociallogin\helpers\RemoteImageUrl::setResolver(null);

    if (is_dir($fixtureRoot)) {
        FileHelper::removeDirectory($fixtureRoot);
    }
}
