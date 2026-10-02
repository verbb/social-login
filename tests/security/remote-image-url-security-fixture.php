<?php

use verbb\sociallogin\helpers\RemoteImageUrl;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use yii\base\InvalidArgumentException;

$vendorPath = getenv('VERBB_SOCIAL_LOGIN_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';

if (!is_file($vendorPath . '/autoload.php')) {
    throw new RuntimeException('Set VERBB_SOCIAL_LOGIN_TEST_VENDOR to a Craft 5 vendor directory.');
}

require $vendorPath . '/autoload.php';
require $vendorPath . '/yiisoft/yii2/Yii.php';
require $vendorPath . '/craftcms/cms/src/Craft.php';
require dirname(__DIR__, 2) . '/src/helpers/RemoteImageUrl.php';

function remoteImageUrlFixtureAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function remoteImageUrlFixtureRejects(string $url, array $trustedHosts = []): bool
{
    try {
        RemoteImageUrl::prepareRequest($url, $trustedHosts);
    } catch (InvalidArgumentException) {
        return true;
    }

    return false;
}

$answers = [
    'public.example.test' => ['93.184.216.34'],
    'mixed.example.test' => ['93.184.216.34', '10.0.0.10'],
    'private.example.test' => ['10.0.0.10'],
    'translation.example.test' => ['64:ff9b:1::10'],
    'benchmark.example.test' => ['2001:2::10'],
    'documentation.example.test' => ['3fff::10'],
    'top-level-reserved.example.test' => ['4000::10'],
    'trusted.example.test' => ['10.0.0.20'],
    'sub.trusted.example.test' => ['10.0.0.20'],
    'unresolved.example.test' => [],
];

RemoteImageUrl::setResolver(fn(string $host): array => $answers[rtrim(strtolower($host), '.')] ?? []);

try {
    $httpRequest = RemoteImageUrl::prepareRequest('http://public.example.test/photo');
    $httpsRequest = RemoteImageUrl::prepareRequest('https://public.example.test/photo');
    $httpOptions = $httpRequest['options'];
    $httpsOptions = $httpsRequest['options'];

    remoteImageUrlFixtureAssert($httpOptions[RequestOptions::ALLOW_REDIRECTS] === false, 'HTTP image redirects must be handled explicitly.');
    remoteImageUrlFixtureAssert($httpsOptions[RequestOptions::ALLOW_REDIRECTS] === false, 'HTTPS image redirects must be handled explicitly.');
    remoteImageUrlFixtureAssert(isset($httpsOptions[RequestOptions::ON_STATS]), 'Direct requests must verify the connected peer address.');

    if (defined('CURLOPT_RESOLVE')) {
        remoteImageUrlFixtureAssert(isset($httpsOptions[RequestOptions::CURL][CURLOPT_RESOLVE]), 'Validated host answers must be pinned for cURL requests.');
    }

    $proxyOptions = RemoteImageUrl::prepareRequest('https://public.example.test/photo', [], false)['options'];
    remoteImageUrlFixtureAssert(!isset($proxyOptions[RequestOptions::ON_STATS]), 'Configured proxies must remain compatible with remote resolution.');

    $configuredOnStatsCalled = false;
    $configuredOptions = [
        RequestOptions::CURL => [CURLOPT_TIMEOUT => 17],
        RequestOptions::ON_STATS => function() use (&$configuredOnStatsCalled): void {
            $configuredOnStatsCalled = true;
        },
    ];
    $mergedOptions = RemoteImageUrl::prepareRequest('https://public.example.test/photo', [], true, $configuredOptions)['options'];
    $mergedOptions[RequestOptions::ON_STATS](new TransferStats(new Request('GET', 'https://public.example.test/photo'), null, 0, null, []));

    remoteImageUrlFixtureAssert($mergedOptions[RequestOptions::CURL][CURLOPT_TIMEOUT] === 17, 'Configured cURL options must be preserved.');
    remoteImageUrlFixtureAssert($configuredOnStatsCalled, 'Configured transfer-stat callbacks must be composed with destination checks.');

    remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects('ftp://public.example.test/photo'), 'Unsupported image URL schemes must be rejected.');
    remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects('https://user@public.example.test/photo'), 'Credential-bearing image URLs must be rejected.');
    remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects('https://@public.example.test/photo'), 'Empty URL credentials must be rejected.');
    remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects('https:///photo'), 'Image URLs without a host must be rejected.');
    remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects('https://2130706433/photo'), 'Ambiguous numeric host forms must be rejected.');
    remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects('https://0177.0.0.1/photo'), 'Non-canonical numeric host forms must be rejected.');
    remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects('https://unresolved.example.test/photo'), 'Unresolved image hosts must be rejected.');
    remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects('https://private.example.test/photo'), 'Private image destinations must be rejected by default.');
    remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects('https://mixed.example.test/photo'), 'Hosts with any prohibited DNS answer must be rejected.');
    remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects('https://translation.example.test/photo'), 'Local-use translation destinations must be rejected.');
    remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects('https://benchmark.example.test/photo'), 'Benchmark destinations must be rejected.');
    remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects('https://documentation.example.test/photo'), 'Reserved documentation destinations must be rejected.');
    remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects('https://top-level-reserved.example.test/photo'), 'DNS answers outside public IPv6 global-unicast space must be rejected.');
    remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects('https://sub.trusted.example.test/photo', ['trusted.example.test']), 'Trusted host entries must not extend to subdomains.');
    remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects('https://192.0.2.10/photo'), 'Reserved literal image destinations must be rejected.');
    remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects('https://[2001:db8::10]/photo'), 'Reserved IPv6 image destinations must be rejected.');

    foreach (['4000::10', '6000::10', '8000::10', 'a000::10', 'c000::10', 'e000::10', 'f000::10', 'fe00::10'] as $reservedIp) {
        remoteImageUrlFixtureAssert(remoteImageUrlFixtureRejects("https://[$reservedIp]/photo"), 'Top-level reserved IPv6 destinations must be rejected.');
    }

    $trustedOptions = RemoteImageUrl::prepareRequest('https://trusted.example.test/photo', ['TRUSTED.EXAMPLE.TEST.'])['options'];
    remoteImageUrlFixtureAssert($trustedOptions[RequestOptions::ALLOW_REDIRECTS] === false, 'An exact config-owned private host exception must remain manually redirected.');

    $publicLiteralOptions = RemoteImageUrl::prepareRequest('https://1.1.1.1/photo')['options'];
    remoteImageUrlFixtureAssert($publicLiteralOptions[RequestOptions::ALLOW_REDIRECTS] === false, 'Public IP literal image URLs must remain supported.');

    $trailingDotRequest = RemoteImageUrl::prepareRequest('https://PUBLIC.EXAMPLE.TEST./photo');
    remoteImageUrlFixtureAssert($trailingDotRequest['url'] === 'https://public.example.test/photo', 'The requested host must use the same canonical form as validation and DNS pinning.');

    if (defined('CURLOPT_RESOLVE')) {
        remoteImageUrlFixtureAssert(str_starts_with($trailingDotRequest['options'][RequestOptions::CURL][CURLOPT_RESOLVE][0], 'public.example.test:443:'), 'The cURL resolve key must match the requested host.');
    }

    echo "Remote image URL security fixture passed.\n";
} finally {
    RemoteImageUrl::setResolver(null);
}
