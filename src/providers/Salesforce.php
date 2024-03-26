<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use craft\helpers\App;

use verbb\auth\providers\Salesforce as SalesforceProvider;

class Salesforce extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return SalesforceProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'salesforce';
    public ?string $apiDomain = null;
    public bool|string $useSandbox = false;


    // Public Methods
    // =========================================================================

    public function getUseSandbox(): string
    {
        return App::parseBooleanEnv($this->useSandbox);
    }

    public function getApiDomain(): string
    {
        $prefix = $this->getUseSandbox() ? 'test' : 'login';

        return "https://{$prefix}.salesforce.com";
    }

    public function getBaseApiUrl(?Token $token): ?string
    {
        $url = $this->getApiDomain();

        return "$url/services/data/v49.0";
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['domain'] = $this->getApiDomain();

        return $config;
    }

    public function getAuthorizationUrlOptions(): array
    {
        $options = parent::getAuthorizationUrlOptions();

        $options['scope'] = [
            'api',
            'openid',
            'refresh_token',
            'offline_access',
        ];
        
        return $options;
    }

}