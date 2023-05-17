<?php
namespace verbb\sociallogin\providers;

use craft\helpers\App;
use verbb\sociallogin\base\OAuthProvider;

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


    public function getTenant() : ?string
    {
        return App::parseEnv($this->tenant);
    }

    public function getOAuthProviderConfig(): array
    {
        return [
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'tenant' => $this->tenant,
            'redirectUri' => $this->getRedirectUri(),
        ];
    }

    public function getUserProfileFields(): array
    {
        return [
            'email',
            'given_name',
            'unique_name',
            'upn',
            'tenant',
        ];
    }

}