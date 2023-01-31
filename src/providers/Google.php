<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use craft\helpers\App;

use verbb\auth\providers\Google as GoogleProvider;

class Google extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return GoogleProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'google';
    public ?string $proxyRedirect = null;


    // Public Methods
    // =========================================================================

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

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'firstName',
            'lastName',
            'locale',
            'hostedDomain',
            'avatar',
        ];
    }

}