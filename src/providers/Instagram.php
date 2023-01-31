<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Instagram as InstagramProvider;

class Instagram extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return InstagramProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'instagram';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'nickname',
        ];
    }

}