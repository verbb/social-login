<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Eventbrite as EventbriteProvider;

class Eventbrite extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return EventbriteProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'eventbrite';
}