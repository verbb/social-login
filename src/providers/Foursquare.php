<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Foursquare as FoursquareProvider;

class Foursquare extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return FoursquareProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'foursquare';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'firstName',
            'lastName',
            'bio',
        ];
    }
    
}