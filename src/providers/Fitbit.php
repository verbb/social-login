<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Fitbit as FitbitProvider;

class Fitbit extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return FitbitProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'fitbit';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'displayName',
        ];
    }

}