<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use craft\helpers\App;

use verbb\auth\providers\Auth0 as Auth0Provider;

class Auth0 extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return Auth0Provider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'auth0';
    public ?string $region = null;
    public ?string $account = null;


    // Public Methods
    // =========================================================================

    public function getRegion(): ?string
    {
        return App::parseEnv($this->region);
    }

    public function getAccount(): ?string
    {
        return App::parseEnv($this->account);
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['region', 'account'], 'required'];

        return $rules;
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['region'] = $this->getRegion();
        $config['account'] = $this->getAccount();

        return $config;
    }

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'nickname',
            'identities',
            'pictureUrl',
        ];
    }

}