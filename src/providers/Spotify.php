<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Spotify as SpotifyProvider;

class Spotify extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return SpotifyProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'spotify';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'birthDate',
            'country',
            'displayName',
            'externalUrls',
            'followers',
            'href',
            'images',
            'product',
            'type',
            'uri',
        ];
    }

}