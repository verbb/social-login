<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\TikTok as TikTokProvider;

class TikTok extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return TikTokProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'tikTok';

}