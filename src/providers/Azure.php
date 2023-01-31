<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Azure as AzureProvider;

class Azure extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return AzureProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'azure';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'firstName',
            'lastName',
            'upn',
            'tenantId',
        ];
    }

}