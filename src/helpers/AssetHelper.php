<?php
namespace verbb\sociallogin\helpers;

use verbb\sociallogin\SocialLogin;

use Craft;
use craft\elements\User;
use craft\helpers\FileHelper;

use Throwable;

class AssetHelper
{
    // Static Methods
    // =========================================================================

    public static function fetchRemoteImage(User $user, string $url, string $filename): ?string
    {
        if (!self::_isValidTemporaryFilename($filename)) {
            return null;
        }

        $tempPath = self::_createTempPath($user) . '/' . $filename;
        $client = Craft::createGuzzleClient();
        $extension = null;

        try {
            // Download the file and save in the temp path
            $response = $client->request('GET', $url, [
                'sink' => $tempPath,
            ]);

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
}
