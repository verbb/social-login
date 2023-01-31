<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Vimeo as VimeoProvider;

class Vimeo extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return VimeoProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'vimeo';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'username',
            'link',
            'avatar',
            'tokenScope',
        ];
    }

}