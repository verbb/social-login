<?php
namespace verbb\sociallogin\helpers;

use Craft;

use yii\base\InvalidArgumentException;

use CraftCms\UrlValidator\UrlValidationException;
use CraftCms\UrlValidator\UrlValidator;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;

class RemoteImageUrl
{
    // Constants
    // =========================================================================

    private const DISALLOWED_IP_RANGES = [
        ['192.0.2.0', 24],
        ['192.88.99.0', 24],
        ['198.51.100.0', 24],
        ['203.0.113.0', 24],
        ['224.0.0.0', 4],
        ['64:ff9b:1::', 48],
        ['100::', 64],
        ['2001:2::', 48],
        ['2001:10::', 28],
        ['2001:20::', 28],
        ['2001:db8::', 32],
        ['3fff::', 20],
        ['5f00::', 16],
        ['ff00::', 8],
    ];


    // Properties
    // =========================================================================

    private static mixed $_resolver = null;


    // Public Methods
    // =========================================================================

    /**
     * Build Guzzle safeguards for a provider-supplied image URL.
     */
    public static function prepareRequest(string $url, array $trustedHosts = [], bool $verifyConnectedIp = true, array $baseOptions = []): array
    {
        try {
            $uri = new Uri($url);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(Craft::t('social-login', 'The remote image URL is invalid.'), previous: $e);
        }

        $host = self::_normalizeHost($uri->getHost());
        $requestHost = str_contains($host, ':') ? "[$host]" : $host;
        $requestUrl = (string)$uri->withHost($requestHost);

        if ($host === '' || !in_array(strtolower($uri->getScheme()), ['http', 'https'], true)) {
            throw new InvalidArgumentException(Craft::t('social-login', 'The remote image URL is not permitted.'));
        }

        if (parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PASS) !== null) {
            throw new InvalidArgumentException(Craft::t('social-login', 'Remote image URLs cannot contain user credentials.'));
        }

        $isIp = filter_var($host, FILTER_VALIDATE_IP) !== false;

        if (!$isIp && preg_match('/^(?:0x[0-9a-f]+|[0-9]+)(?:\.(?:0x[0-9a-f]+|[0-9]+))*$/i', $host)) {
            throw new InvalidArgumentException(Craft::t('social-login', 'The remote image URL contains an invalid host.'));
        }

        $trusted = self::_isTrustedHost($host, $trustedHosts);
        $validator = self::_validator($trusted);

        try {
            if ($isIp) {
                if (!self::_validateIp($validator, $host, $trusted)) {
                    throw new UrlValidationException("$url contains an invalid IP address.");
                }

                $ips = [$host];
            } else {
                $ips = $validator->validate($requestUrl);

                foreach ($ips as $ip) {
                    if (!self::_validateIp($validator, $ip, $trusted)) {
                        throw new UrlValidationException("$url resolves to an invalid IP address.");
                    }
                }
            }
        } catch (UrlValidationException $e) {
            throw new InvalidArgumentException(Craft::t('social-login', 'The remote image URL is not a permitted remote address.'), previous: $e);
        }

        $options = $baseOptions;
        $options[RequestOptions::ALLOW_REDIRECTS] = false;

        if ($verifyConnectedIp) {
            $configuredOnStats = $options[RequestOptions::ON_STATS] ?? null;
            $options[RequestOptions::ON_STATS] = function(TransferStats $stats) use ($configuredOnStats, $trusted, $validator) {
                if (is_callable($configuredOnStats)) {
                    $configuredOnStats($stats);
                }

                $ip = $stats->getHandlerStat('primary_ip');

                if ($ip && !self::_validateIp($validator, $ip, $trusted)) {
                    throw new InvalidArgumentException(Craft::t('social-login', 'The remote image server resolved to a prohibited address.'));
                }
            };
        }

        // Pin cURL to the validated answers so DNS cannot change between
        // validation and connection. Raw IP URLs already identify their peer.
        if (!$isIp && defined('CURLOPT_RESOLVE')) {
            $port = $uri->getPort() ?? ($uri->getScheme() === 'https' ? 443 : 80);
            $addresses = array_map(fn(string $ip): string => str_contains($ip, ':') ? "[$ip]" : $ip, $ips);
            $curlVersion = curl_version()['version_number'] ?? PHP_INT_MAX;

            if ($curlVersion < 0x073B00) {
                $addresses = [reset($addresses)];
            }

            $curlOptions = is_array($options[RequestOptions::CURL] ?? null) ? $options[RequestOptions::CURL] : [];
            $curlOptions[CURLOPT_RESOLVE] = ["$host:$port:" . implode(',', $addresses)];
            $options[RequestOptions::CURL] = $curlOptions;
        }

        return [
            'url' => $requestUrl,
            'options' => $options,
        ];
    }

    /** Test seam for deterministic DNS without weakening production defaults. */
    public static function setResolver(?callable $resolver): void
    {
        self::$_resolver = $resolver;
    }


    // Private Methods
    // =========================================================================

    private static function _isTrustedHost(string $host, array $trustedHosts): bool
    {
        $trustedHosts = array_filter(array_map(function(mixed $trustedHost): string {
            return is_string($trustedHost) ? self::_normalizeHost($trustedHost) : '';
        }, $trustedHosts));

        return in_array($host, $trustedHosts, true);
    }

    private static function _validator(bool $trusted): UrlValidator
    {
        $options = null;

        if ($trusted) {
            // A config-file allowlist is an administrator-owned exception for
            // intentional private image servers. URL syntax and DNS pinning still apply.
            $options = [
                'disallowedHostnames' => [],
                'disallowedIpv4Addresses' => [],
                'disallowedIpv4Ranges' => [],
                'ipv4FilterFlags' => FILTER_FLAG_IPV4,
                'ipv6FilterFlags' => FILTER_FLAG_IPV6,
            ];
        }

        return new UrlValidator(self::$_resolver, $options);
    }

    private static function _normalizeHost(string $host): string
    {
        return rtrim(strtolower(trim($host, '[]')), '.');
    }

    private static function _validateIp(UrlValidator $validator, string $ip, bool $trusted): bool
    {
        if (!$validator->validateIp($ip)) {
            return false;
        }

        if (!$trusted) {
            if (str_contains($ip, ':') && !self::_ipInRange($ip, '2000::', 3)) {
                return false;
            }

            foreach (self::DISALLOWED_IP_RANGES as [$subnet, $bits]) {
                if (self::_ipInRange($ip, $subnet, $bits)) {
                    return false;
                }
            }
        }

        return true;
    }

    private static function _ipInRange(string $ip, string $subnet, int $bits): bool
    {
        $packedIp = @inet_pton($ip);
        $packedSubnet = @inet_pton($subnet);

        if ($packedIp === false || $packedSubnet === false || strlen($packedIp) !== strlen($packedSubnet)) {
            return false;
        }

        $bytes = intdiv($bits, 8);
        $remainingBits = $bits % 8;

        if ($bytes && substr($packedIp, 0, $bytes) !== substr($packedSubnet, 0, $bytes)) {
            return false;
        }

        if (!$remainingBits) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($packedIp[$bytes]) & $mask) === (ord($packedSubnet[$bytes]) & $mask);
    }
}
