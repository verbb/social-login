<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\DeviantArt as DeviantArtProvider;

class DeviantArt extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return DeviantArtProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'deviantArt';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'icon',
            'type',
        ];
    }

}