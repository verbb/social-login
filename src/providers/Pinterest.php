<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Pinterest as PinterestProvider;

class Pinterest extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return PinterestProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'pinterest';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'imageurl',
            'avatar',
            'pictureUrl',
            'firstName',
            'lastName',
            'nickname',
            'username',
            'url',
        ];
    }

}