<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use craft\helpers\App;

use verbb\auth\models\Token;
use verbb\auth\models\UserProfile;
use verbb\auth\providers\Salesforce as SalesforceProvider;

class Salesforce extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return SalesforceProvider::class;
    }

    public static function supportsAdminRegistration(): bool
    {
        return true;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'salesforce';
    public ?string $apiDomain = null;
    public ?string $expectedOrganizationId = null;
    public bool|string $useSandbox = false;


    // Public Methods
    // =========================================================================

    public function getUseSandbox(): string
    {
        return App::parseBooleanEnv($this->useSandbox);
    }

    public function getExpectedOrganizationId(): ?string
    {
        return App::parseEnv($this->expectedOrganizationId);
    }

    public function settingsAttributes(): array
    {
        return array_merge(parent::settingsAttributes(), ['expectedOrganizationId']);
    }

    public function canRegisterAdmin(UserProfile $userProfile): bool
    {
        $expectedOrganizationId = $this->getExpectedOrganizationId();
        $organizationId = $userProfile->organizationId;

        return parent::canRegisterAdmin($userProfile) && $expectedOrganizationId && is_string($organizationId) && hash_equals($expectedOrganizationId, $organizationId);
    }

    public function getUserProfileFields(): array
    {
        return ['organizationId'];
    }

    public function getApiDomain(): string
    {
        $prefix = $this->getUseSandbox() ? 'test' : 'login';

        return "https://{$prefix}.salesforce.com";
    }

    public function getBaseApiUrl(?Token $token): ?string
    {
        $url = $this->getApiDomain();

        return "$url/services/data/v49.0";
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['domain'] = $this->getApiDomain();

        return $config;
    }

    public function getAuthorizationUrlOptions(): array
    {
        $options = parent::getAuthorizationUrlOptions();

        $options['scope'] = [
            'api',
            'openid',
            'refresh_token',
            'offline_access',
        ];
        
        return $options;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['expectedOrganizationId'], 'required', 'when' => fn() => $this->allowAdminRegistration];

        return $rules;
    }

}
