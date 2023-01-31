<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

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


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'domain',
            'country',
            'shopOwner',
        ];
    }

}