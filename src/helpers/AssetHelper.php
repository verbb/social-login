<?php
namespace verbb\sociallogin\helpers;

use verbb\sociallogin\SocialLogin;

use Craft;
use craft\elements\User;
use craft\helpers\FileHelper;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\RequestOptions;

use Throwable;

class AssetHelper
{
    // Constants
    // =========================================================================

    private const MAX_REDIRECTS = 5;


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
        $extension = null;

        try {
            $redirects = 0;

            while (true) {
                $baseOptions = array_filter([
                    RequestOptions::CURL => $client->getConfig(RequestOptions::CURL),
                    RequestOptions::ON_STATS => $client->getConfig(RequestOptions::ON_STATS),
                ], fn(mixed $value): bool => $value !== null);
                $request = RemoteImageUrl::prepareRequest($url, $trustedHosts, $verifyConnectedIp, $baseOptions);
                $url = $request['url'];
                $options = $request['options'];
                $options[RequestOptions::SINK] = $tempPath;
                $options[RequestOptions::HTTP_ERRORS] = false;
                $response = $client->request('GET', $url, $options);
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
