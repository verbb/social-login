<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use craft\helpers\App;

use verbb\auth\providers\IdentityServer4 as IdentityServer4Provider;

class IdentityServer4 extends Auth0
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return IdentityServer4Provider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'identityServer4';

}