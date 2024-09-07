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
    public ?string $endpointVersion = '1.0';
    public ?string $tenant = 'common';


    // Public Methods
    // =========================================================================

    public function getEndpointVersion(): ?string
    {
        return App::parseEnv($this->endpointVersion);
    }

    public function getTenant(): ?string
    {
        return App::parseEnv($this->tenant);
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['defaultEndPointVersion'] = $this->getEndpointVersion();
        $config['tenant'] = $this->getTenant();

        return $config;
    }

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'given_name',
            'family_name',
            'unique_name',
            'upn',
            'tenant',
        ];
    }

}
