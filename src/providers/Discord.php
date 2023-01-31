<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Discord as DiscordProvider;

class Discord extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return DiscordProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'discord';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'username',
            'discriminator',
            'avatarHash',
            'verified',
        ];
    }

}