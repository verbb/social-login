<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use Craft;

use verbb\auth\providers\AmazonCognito as AmazonCognitoProvider;

class AmazonCognito extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('social-login', 'Amazon Cognito');
    }

    public static function getOAuthProviderClass(): string
    {
        return AmazonCognitoProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'amazonCognito';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'postalCode',
        ];
    }

}