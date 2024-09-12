<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use Craft;

use verbb\auth\providers\Microsoft as MicrosoftProvider;

class Microsoft extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('social-login', 'Microsoft (Legacy)');
    }

    public static function getOAuthProviderClass(): string
    {
        return MicrosoftProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'microsoft';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'firstname',
            'lastname',
            'name',
            'urls',
        ];
    }

}