<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Snapchat as SnapchatProvider;

class Snapchat extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return SnapchatProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'snapchat';


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