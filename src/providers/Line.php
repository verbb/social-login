<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Line as LineProvider;

class Line extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return LineProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'line';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'avatar',
            'statusMessage',
        ];
    }

}