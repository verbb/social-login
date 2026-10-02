<?php

use verbb\sociallogin\helpers\AssetHelper;

use craft\elements\User;
use craft\helpers\FileHelper;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

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
    public MockHandler $handler;

    public function getConfigFromFile(string $filename): array
    {
        if ($filename === 'guzzle') {
            return ['handler' => $this->handler];
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
