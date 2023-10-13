<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use craft\helpers\App;

use verbb\auth\providers\Shopify as ShopifyProvider;

class Shopify extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return ShopifyProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'shopify';
    public ?string $subdomain = null;


    // Public Methods
    // =========================================================================

    public function getSubdomain(): ?string
    {
        return App::parseEnv($this->subdomain);
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['shop'] = $this->getSubdomain();

        return $config;
    }

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'domain',
            'country',
            'shopOwner',
        ];
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['subdomain'], 'required'];

        return $rules;
    }

}