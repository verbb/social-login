<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Tumblr as TumblrProvider;

class Tumblr extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return TumblrProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'tumblr';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'nickname',
        ];
    }

}