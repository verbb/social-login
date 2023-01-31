<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Twitch as TwitchProvider;

class Twitch extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return TwitchProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'twitch';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'login',
            'displayName',
            'broadcasterType',
            'description',
            'profileImageUrl',
            'offlineImageUrl',
            'viewCount',
            'type',
        ];
    }

}