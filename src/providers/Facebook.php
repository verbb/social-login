<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Facebook as FacebookProvider;

class Facebook extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return FacebookProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'facebook';


    // Public Methods
    // =========================================================================

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['graphApiVersion'] = 'v3.3';

        return $config;
    }

    public function getUserProfileFields(): array
    {
        return [
            'firstName',
            'lastName',
            'hometown',
            'locale',
            'link',
            'timezone',
            'minAge',
            'maxAge',
            'pictureUrl',
            'coverPhotoUrl',
        ];
    }

}