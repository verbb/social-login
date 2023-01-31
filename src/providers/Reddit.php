<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Reddit as RedditProvider;

class Reddit extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return RedditProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'reddit';

}