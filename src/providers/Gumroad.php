<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Gumroad as GumroadProvider;

class Gumroad extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return GumroadProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'gumroad';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'bio',
            'identities',
            'facebookProfile',
            'twitterHandle',
            'profileUrl',
        ];
    }

}