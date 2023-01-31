<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Dropbox as DropboxProvider;

class Dropbox extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return DropboxProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'dropbox';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
        ];
    }

}