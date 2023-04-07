<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use Craft;
use craft\helpers\App;

use verbb\auth\providers\NeonCrm as NeonCrmProvider;

class NeonCrm extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('social-login', 'Neon CRM');
    }

    public static function getOAuthProviderClass(): string
    {
        return NeonCrmProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'neonCrm';
    public ?string $organizationId = null;
    public ?string $proxyRedirect = null;
    public ?string $apiKey = null;


    // Public Methods
    // =========================================================================

    public function getApiKey(): ?string
    {
        return App::parseEnv($this->apiKey);
    }

    public function getOrganizationId(): ?string
    {
        return App::parseEnv($this->organizationId);
    }

    public function getProxyRedirect(): ?bool
    {
        return App::parseBooleanEnv($this->proxyRedirect);
    }

    public function getRedirectUri(): ?string
    {
        $uri = parent::getRedirectUri();

        // Allow a proxy to our server to forward on the request - just for local dev ease
        if ($this->getProxyRedirect()) {
            return "https://formie.verbb.io?return=$uri";
        }

        return $uri;
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['organizationId'] = $this->getOrganizationId();
        $config['apiKey'] = $this->getApiKey();

        return $config;
    }

    public function getUserProfileFields(): array
    {
        return [
            'name',
        ];
    }

}