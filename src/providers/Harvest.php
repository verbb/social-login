<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Harvest as HarvestProvider;

class Harvest extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return HarvestProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'harvest';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'avatar',
        ];
    }

}