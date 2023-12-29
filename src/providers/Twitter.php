<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use League\OAuth1\Client\Server\Twitter as TwitterProvider;

class Twitter extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return TwitterProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'twitter';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'nickname',
            'location',
            'description',
            'imageUrl',
        ];
    }

}