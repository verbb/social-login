<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\StackExchange as StackExchangeProvider;

class StackExchange extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return StackExchangeProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'stackExchange';

}