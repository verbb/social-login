<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Unsplash as UnsplashProvider;

class Unsplash extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return UnsplashProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'unsplash';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'username',
            'name',
            'firstName',
            'lastName',
        ];
    }

}