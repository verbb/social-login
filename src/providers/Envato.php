<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Envato as EnvatoProvider;

class Envato extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return EnvatoProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'envato';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'username',
            'purchases',
            'purchasesCount',
        ];
    }

}