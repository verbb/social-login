<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use Craft;
use craft\helpers\App;

use verbb\auth\providers\AmazonCognito as AmazonCognitoProvider;

class AmazonCognito extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('social-login', 'Amazon Cognito');
    }

    public static function getOAuthProviderClass(): string
    {
        return AmazonCognitoProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'amazonCognito';
    public ?string $domain = null;


    // Public Methods
    // =========================================================================

    public function getDomain(): ?string
    {
        return App::parseEnv($this->domain);
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['domain'] = $this->getDomain();

        return $config;
    }

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'postalCode',
        ];
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['domain'], 'required'];

        return $rules;
    }

}