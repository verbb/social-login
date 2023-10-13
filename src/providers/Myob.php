<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use craft\helpers\App;

use verbb\auth\providers\Myob as MyobProvider;

class Myob extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return MyobProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'myob';
    public ?string $username = null;
    public ?string $password = null;
    public ?string $companyName = null;


    // Public Methods
    // =========================================================================

    public function getUsername(): ?string
    {
        return App::parseEnv($this->username);
    }

    public function getPassword(): ?string
    {
        return App::parseEnv($this->password);
    }

    public function getCompanyName(): ?string
    {
        return App::parseEnv($this->companyName);
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['username'] = $this->getUsername();
        $config['password'] = $this->getPassword();
        $config['companyName'] = $this->getCompanyName();

        return $config;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['username', 'password', 'companyName'], 'required'];

        return $rules;
    }

}