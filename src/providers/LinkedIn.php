<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\LinkedIn as LinkedInProvider;

class LinkedIn extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return LinkedInProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'linkedIn';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'firstName',
            'lastName',
            'imageUrl',
            'url',
        ];
    }

}