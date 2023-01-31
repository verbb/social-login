<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Slack as SlackProvider;

class Slack extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return SlackProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'slack';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'url',
            'team',
            'user',
            'teamId',
            'userId',
        ];
    }

}