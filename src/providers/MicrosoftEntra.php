<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use Craft;
use craft\helpers\App;

use verbb\auth\providers\MicrosoftEntra as MicrosoftEntraProvider;

class MicrosoftEntra extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('social-login', 'Microsoft Entra');
    }

    public static function supportsLogin(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return MicrosoftEntraProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'microsoftEntra';
    public ?string $tenant = 'common';


    // Public Methods
    // =========================================================================

    public function getTenant(): ?string
    {
        return App::parseEnv($this->tenant);
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['tenant'] = $this->getTenant();

        return $config;
    }

    public function getAuthorizationUrlOptions(): array
    {
        $options = parent::getAuthorizationUrlOptions();

        $options['scope'] = [
            'User.Read',
        ];
        
        return $options;
    }

    public function getUserProfileFields(): array
    {
        return [
            'fullName',
            'firstName',
            'lastName',
            'upn',
            'jobTitle',
            'mobilePhone',
            'businessPhone',
        ];
    }

}
