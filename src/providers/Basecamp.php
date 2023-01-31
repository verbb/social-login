<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Basecamp as BasecampProvider;

class Basecamp extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return BasecampProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'basecamp';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
        ];
    }

}