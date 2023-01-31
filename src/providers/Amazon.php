<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Amazon as AmazonProvider;

class Amazon extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return AmazonProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'amazon';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'postalCode',
        ];
    }

}