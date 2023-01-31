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
        $tempPath = self::createTempPath($user) . '/' . $filename;
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
                } else if ($mimeType === 'image/jpeg') {
                    $extension = 'jpg';
                } else if ($mimeType === 'image/png') {
                    $extension = 'png';
                } else if ($mimeType === 'image/svg+xml') {
                    $extension = 'svg';
                }
            }

            if (!$extension) {
                return null;
            }

            // Now we have an extension, rename the downloaded, extension-less file
            rename($tempPath, $tempPath . '.' . $extension);

            return $tempPath . '.' . $extension;
        } catch (Throwable $e) {
            SocialLogin::error('Error fetching remote image “{email}” - “{url}” for “{provider}”: “{message}” {file}:{line}', [
                'email' => $user->email,
                'url' => $url,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        return null;
    }

    private static function createTempPath(User $user): string
    {
        $tempPath = Craft::$app->getPath()->getTempPath() . '/social-login/' . $user->email . '/';

        if (!is_dir($tempPath)) {
            FileHelper::createDirectory($tempPath);
        }

        return $tempPath;
    }
}