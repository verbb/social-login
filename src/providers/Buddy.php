<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Buddy as BuddyProvider;

class Buddy extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return BuddyProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'buddy';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'url',
            'workspaceUrl',
            'avatarUrl',
            'name',
            'title',
        ];
    }

}