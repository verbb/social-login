<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Square as SquareProvider;

class Square extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return SquareProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'square';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'countryCode',
            'languageCode',
            'currencyCode',
            'businessName',
            'businessAddress',
            'businessPhone',
            'businessType',
            'shippingAddress',
            'accountType',
            'accountCapabilities',
            'locationDetails',
            'marketUrl',
        ];
    }

}