<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use craft\helpers\App;

use verbb\auth\providers\Zendesk as ZendeskProvider;

class Zendesk extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return ZendeskProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'zendesk';
    public ?string $subdomain = null;


    // Public Methods
    // =========================================================================

    public function getSubdomain(): ?string
    {
        return App::parseEnv($this->subdomain);
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['subdomain'], 'required'];

        return $rules;
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['subdomain'] = $this->getSubdomain();

        return $config;
    }

    public function getUserProfileFields(): array
    {
        return [
            'name',
        ];
    }

}