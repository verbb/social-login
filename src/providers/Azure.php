<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use Craft;
use craft\helpers\App;

use verbb\auth\providers\Azure as AzureProvider;
use verbb\auth\models\UserProfile;

class Azure extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return true;
    }

    public static function supportsAdminRegistration(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return AzureProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'azure';
    public ?string $endpointVersion = '1.0';
    public ?string $tenant = 'common';


    // Public Methods
    // =========================================================================

    public function getEndpointVersion(): ?string
    {
        return App::parseEnv($this->endpointVersion);
    }

    public function getTenant(): ?string
    {
        return App::parseEnv($this->tenant);
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['defaultEndPointVersion'] = $this->getEndpointVersion();
        $config['tenant'] = $this->getTenant();

        return $config;
    }

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'given_name',
            'family_name',
            'unique_name',
            'upn',
            'tenant',
        ];
    }

    public function canRegisterAdmin(UserProfile $userProfile): bool
    {
        $tenant = strtolower(trim((string)$this->getTenant()));

        return parent::canRegisterAdmin($userProfile) && $tenant !== '' && !in_array($tenant, ['common', 'organizations', 'consumers'], true);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['tenant'], function($attribute) {
            $tenant = strtolower(trim((string)$this->getTenant()));

            if ($this->allowAdminRegistration && ($tenant === '' || in_array($tenant, ['common', 'organizations', 'consumers'], true))) {
                $this->addError($attribute, Craft::t('social-login', 'A specific tenant is required for administrator registration.'));
            }
        }];

        return $rules;
    }

}
