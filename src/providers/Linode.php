<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Linode as LinodeProvider;

class Linode extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return LinodeProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'linode';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'firstName',
            'lastName',
            'company',
            'address',
            'phone',
            'taxId',
            'balance',
            'creditCard',
        ];
    }

}