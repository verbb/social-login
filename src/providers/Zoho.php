<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Zoho as ZohoProvider;

class Zoho extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return ZohoProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'zoho';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'zUID',
            'displayName',
            'firstName',
            'lastName',
        ];
    }

}