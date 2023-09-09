<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use craft\helpers\App;

use verbb\auth\providers\PayPal as PayPalProvider;

class PayPal extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return PayPalProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'payPal';
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
            'givenName',
            'familyName',
            'gender',
            'birthdate',
            'zoneinfo',
            'locale',
            'phoneNumber',
            'address',
            'accountType',
            'ageRange',
            'payerId',
        ];
    }

}