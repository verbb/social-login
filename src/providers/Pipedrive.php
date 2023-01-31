<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Pipedrive as PipedriveProvider;

class Pipedrive extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return PipedriveProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'pipedrive';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'companyId',
        ];
    }

}