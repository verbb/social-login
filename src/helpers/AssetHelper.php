<?php
namespace verbb\sociallogin\helpers;

use verbb\sociallogin\SocialLogin;

use Craft;
use craft\elements\User;
use craft\helpers\FileHelper;

use RuntimeException;
use Throwable;

use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class AssetHelper
{
    // Constants
    // =========================================================================

    private const MAX_CONNECT_TIME = 5.0;
    private const MAX_DOWNLOAD_BYTES = 10 * 1024 * 1024;
    private const MAX_REDIRECTS = 5;
    private const MAX_TRANSFER_TIME = 15.0;


    // Static Methods
    // =========================================================================

    public static function fetchRemoteImage(User $user, string $url, string $filename): ?string
    {
        if (!self::_isValidTemporaryFilename($filename)) {
            return null;
        }

        $client = Craft::createGuzzleClient();
        $trustedHosts = self::_trustedRemoteImageHosts();
        $verifyConnectedIp = !$client->getConfig('proxy');
        $tempPath = self::_createTempPath($user) . '/' . $filename;
        $curlOptions = $client->getConfig(RequestOptions::CURL);
        $curlOptions = is_array($curlOptions) ? $curlOptions : [];
        $connectTimeout = self::_boundedTimeout(
            self::MAX_CONNECT_TIME,
            $client->getConfig(RequestOptions::CONNECT_TIMEOUT),
            self::_removeCurlTimeout($curlOptions, 'CURLOPT_CONNECTTIMEOUT'),
            self::_removeCurlTimeout($curlOptions, 'CURLOPT_CONNECTTIMEOUT_MS', 1000),
        );
        $transferTimeout = self::_boundedTimeout(
            self::MAX_TRANSFER_TIME,
            $client->getConfig(RequestOptions::TIMEOUT),
            self::_removeCurlTimeout($curlOptions, 'CURLOPT_TIMEOUT'),
            self::_removeCurlTimeout($curlOptions, 'CURLOPT_TIMEOUT_MS', 1000),
        );

        // Raw cURL controls are applied after Guzzle's managed options, so they must not replace these safeguards.
        foreach ([
            'CURLOPT_FILE',
            'CURLOPT_FOLLOWLOCATION',
            'CURLOPT_MAXREDIRS',
            'CURLOPT_NOSIGNAL',
            'CURLOPT_POSTREDIR',
            'CURLOPT_REDIR_PROTOCOLS',
            'CURLOPT_REDIR_PROTOCOLS_STR',
            'CURLOPT_WRITEFUNCTION',
        ] as $curlOption) {
            self::_removeCurlOption($curlOptions, $curlOption);
        }

        $configuredOnHeaders = $client->getConfig(RequestOptions::ON_HEADERS);
        $canInspectHeaders = !self::_hasCurlOption($curlOptions, 'CURLOPT_HEADERFUNCTION');
        $extension = null;

        try {
            $deadline = microtime(true) + self::MAX_TRANSFER_TIME;
            $redirects = 0;
            $remainingBytes = self::MAX_DOWNLOAD_BYTES;

            while (true) {
                $baseOptions = array_filter([
                    RequestOptions::CURL => $curlOptions,
                    RequestOptions::ON_STATS => $client->getConfig(RequestOptions::ON_STATS),
                ], fn(mixed $value): bool => $value !== null);
                $request = RemoteImageUrl::prepareRequest($url, $trustedHosts, $verifyConnectedIp, $baseOptions);
                $url = $request['url'];
                $options = $request['options'];
                $remainingTime = $deadline - microtime(true);

                if ($remainingTime <= 0) {
                    throw new RuntimeException('The remote image download exceeded its time limit.');
                }

                $options[RequestOptions::CONNECT_TIMEOUT] = min($connectTimeout, $remainingTime);
                $options[RequestOptions::HTTP_ERRORS] = false;
                $options[RequestOptions::TIMEOUT] = min($transferTimeout, $remainingTime);

                if ($canInspectHeaders) {
                    $options[RequestOptions::ON_HEADERS] = static function(ResponseInterface $response) use (&$remainingBytes, $configuredOnHeaders): void {
                        $contentLength = trim($response->getHeaderLine('Content-Length'));

                        if ($contentLength !== '' && preg_match('/^\d+$/D', $contentLength) && (float)$contentLength > $remainingBytes) {
                            throw new RuntimeException('The remote image exceeds the download size limit.');
                        }

                        if ($configuredOnHeaders !== null) {
                            $configuredOnHeaders($response);
                        }
                    };
                }

                // Bound bytes at the sink so chunked, compressed, or dishonest responses cannot bypass headers.
                $sink = self::_createBoundedSink($tempPath, $remainingBytes);
                $options[RequestOptions::SINK] = $sink;

                try {
                    $response = $client->request('GET', $url, $options);
                } finally {
                    $sink->close();
                }

                $statusCode = $response->getStatusCode();

                if (!in_array($statusCode, [301, 302, 303, 307, 308], true)) {
                    break;
                }

                if ($redirects >= self::MAX_REDIRECTS || !$response->hasHeader('Location')) {
                    return null;
                }

                $location = trim($response->getHeaderLine('Location'));

                if ($location === '') {
                    return null;
                }

                $url = (string)UriResolver::resolve(new Uri($url), new Uri($location));
                $redirects++;
            }

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            // Get the mime type from the downloaded file
            $mimeType = FileHelper::getMimeType($tempPath);

            // Using `FileHelper::getExtensionByMimeType()` seems to produce gross results `jfif` for `image/jpeg`
            if ($mimeType) {
                if ($mimeType === 'image/gif') {
                    $extension = 'gif';
                } elseif ($mimeType === 'image/jpeg') {
                    $extension = 'jpg';
                } elseif ($mimeType === 'image/png') {
                    $extension = 'png';
                } elseif ($mimeType === 'image/svg+xml') {
                    $extension = 'svg';
                }
            }

            if (!$extension) {
                return null;
            }

            // Now we have an extension, rename the downloaded, extension-less file
            $imagePath = $tempPath . '.' . $extension;

            if (!rename($tempPath, $imagePath)) {
                return null;
            }

            return $imagePath;
        } catch (Throwable $e) {
            SocialLogin::error('Error fetching remote image “{email}” - “{url}” for “{provider}”: “{message}” {file}:{line}', [
                'email' => $user->email,
                'url' => $url,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        } finally {
            // Successful downloads have already been renamed, while rejected or interrupted downloads retain this path.
            if (is_file($tempPath)) {
                FileHelper::unlink($tempPath);
            }
        }

        return null;
    }

    private static function _isValidTemporaryFilename(string $filename): bool
    {
        return $filename !== '' &&
            $filename !== '.' &&
            $filename !== '..' &&
            !str_contains($filename, "\0") &&
            !str_contains($filename, '/') &&
            !str_contains($filename, '\\');
    }

    private static function _boundedTimeout(float $ceiling, mixed ...$configuredTimeouts): float
    {
        foreach ($configuredTimeouts as $configuredTimeout) {
            if (is_numeric($configuredTimeout) && (float)$configuredTimeout > 0) {
                $ceiling = min($ceiling, (float)$configuredTimeout);
            }
        }

        return $ceiling;
    }

    private static function _createBoundedSink(string $tempPath, int &$remainingBytes): StreamInterface
    {
        $stream = Utils::streamFor(Utils::tryFopen($tempPath, 'w+'));

        return FnStream::decorate($stream, [
            'write' => static function(string $data) use ($stream, &$remainingBytes): int {
                $length = strlen($data);

                if ($length > $remainingBytes) {
                    throw new RuntimeException('The remote image exceeds the download size limit.');
                }

                $written = $stream->write($data);
                $remainingBytes -= $written;

                return $written;
            },
        ]);
    }

    private static function _hasCurlOption(array $curlOptions, string $constant): bool
    {
        return defined($constant) && array_key_exists(constant($constant), $curlOptions);
    }

    private static function _removeCurlTimeout(array &$curlOptions, string $constant, int $divisor = 1): ?float
    {
        $value = self::_removeCurlOption($curlOptions, $constant);

        if (!is_numeric($value) || (float)$value <= 0) {
            return null;
        }

        return (float)$value / $divisor;
    }

    private static function _removeCurlOption(array &$curlOptions, string $constant): mixed
    {
        if (!defined($constant)) {
            return null;
        }

        $option = constant($constant);
        $value = $curlOptions[$option] ?? null;
        unset($curlOptions[$option]);

        return $value;
    }

    private static function _createTempPath(User $user): string
    {
        $identity = hash('sha256', (string)$user->email);
        $request = Craft::$app->getSecurity()->generateRandomString(32);
        $tempPath = Craft::$app->getPath()->getTempPath() . '/social-login/' . $identity . '/' . $request;

        if (!is_dir($tempPath)) {
            FileHelper::createDirectory($tempPath);
        }

        return $tempPath;
    }

    private static function _trustedRemoteImageHosts(): array
    {
        $config = Craft::$app->getConfig()->getConfigFromFile('social-login');
        $trustedHosts = is_array($config) ? ($config['trustedRemoteImageHosts'] ?? []) : [];

        return is_array($trustedHosts) ? $trustedHosts : [];
    }
}
