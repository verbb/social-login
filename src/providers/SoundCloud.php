<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\SoundCloud as SoundCloudProvider;

class SoundCloud extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return SoundCloudProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'soundCloud';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'country',
            'firstname',
            'locale',
            'lastname',
            'uri',
            'fullName',
            'avatarUrl',
            'online',
        ];
    }

}