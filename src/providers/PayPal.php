<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\PayPal as PayPalProvider;

class PayPal extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return PayPalProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'payPal';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'givenName',
            'familyName',
            'gender',
            'birthdate',
            'zoneinfo',
            'locale',
            'phoneNumber',
            'address',
            'accountType',
            'ageRange',
            'payerId',
        ];
    }

}