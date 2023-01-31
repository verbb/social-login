<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Imgur as ImgurProvider;

class Imgur extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return ImgurProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'imgur';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'url',
            'bio',
            'reputation',
            'created',
            'proExpiration',
        ];
    }

}