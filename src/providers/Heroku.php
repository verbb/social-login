<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Heroku as HerokuProvider;

class Heroku extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return HerokuProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'heroku';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
        ];
    }

}