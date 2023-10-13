<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use craft\helpers\App;

use verbb\auth\providers\Vend as VendProvider;

class Vend extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return VendProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'vend';
    public ?string $storeName = null;


    // Public Methods
    // =========================================================================

    public function getStoreName(): ?string
    {
        return App::parseEnv($this->storeName);
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['storeName'] = $this->getStoreName();

        return $config;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['storeName'], 'required'];

        return $rules;
    }

}