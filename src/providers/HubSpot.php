<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\HubSpot as HubSpotProvider;

class HubSpot extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return HubSpotProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'hubSpot';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'hubSpotDomain',
        ];
    }

}