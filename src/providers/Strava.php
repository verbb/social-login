<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Strava as StravaProvider;

class Strava extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return StravaProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'strava';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'firstName',
            'lastName',
            'premium',
        ];
    }

}