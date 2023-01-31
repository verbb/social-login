<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Box as BoxProvider;

class Box extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return BoxProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'box';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'avatarUrl',
        ];
    }

}