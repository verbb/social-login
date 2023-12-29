<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use craft\helpers\App;

use verbb\auth\providers\FreeAgent as FreeAgentProvider;

class FreeAgent extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return FreeAgentProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'freeAgent';
    public bool|string $useSandbox = false;


    // Public Methods
    // =========================================================================

    public function getUseSandbox(): string
    {
        return App::parseBooleanEnv($this->useSandbox);
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['isSandbox'] = $this->getUseSandbox();

        return $config;
    }

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'firstName',
            'lastName',
        ];
    }

}