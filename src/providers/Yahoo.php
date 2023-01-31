<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Yahoo as YahooProvider;

class Yahoo extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return YahooProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'yahoo';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'firstName',
            'lastName',
            'avatar',
        ];
    }

}