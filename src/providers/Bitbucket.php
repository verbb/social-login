<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Bitbucket as BitbucketProvider;

class Bitbucket extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return BitbucketProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'bitbucket';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'username',
            'location',
        ];
    }

}