<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use craft\helpers\App;

use verbb\auth\providers\Azure as AzureProvider;

class Azure extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return AzureProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'azure';
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

    public function getUserProfileFields(): array
    {
        return [
            'given_name',
            'unique_name',
            'upn',
            'tenant',
        ];
    }

}