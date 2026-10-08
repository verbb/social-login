<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\base\OAuthProvider;

use Craft;
use craft\elements\User;
use craft\helpers\App;

use RuntimeException;
use Throwable;

use League\OAuth2\Client\Token\AccessToken;
use verbb\auth\models\Token;
use verbb\auth\models\UserProfile;
use verbb\auth\providers\MicrosoftEntra as MicrosoftEntraProvider;

class MicrosoftEntra extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('social-login', 'Microsoft Entra');
    }

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
        return MicrosoftEntraProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'microsoftEntra';
    public ?string $tenant = 'common';
    public bool $trustTenantEmail = false;
    public string $trustedEmailDomains = '';

    private ?UserProfile $_emailMatchingProfile = null;
    private ?AccessToken $_emailMatchingToken = null;
    private array $_emailMatchingContext = [];
    private ?bool $_canTrustEmailMatch = null;


    // Public Methods
    // =========================================================================

    public function getTenant(): ?string
    {
        return App::parseEnv($this->tenant);
    }

    public function getTrustedEmailDomains(): array
    {
        $value = App::parseEnv($this->trustedEmailDomains);

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $domains = preg_split('/[\s,]+/', strtolower(trim($value)));

        foreach ($domains as $domain) {
            if (!$this->_isDomain($domain)) {
                return [];
            }
        }

        return array_values(array_unique($domains));
    }

    public function getUserProfile(Token $token): UserProfile
    {
        // Keep matching authority private and scoped to the profile fetched with this token.
        $this->_emailMatchingProfile = null;
        $this->_emailMatchingToken = null;
        $this->_emailMatchingContext = [];
        $this->_canTrustEmailMatch = null;
        $profile = parent::getUserProfile($token);

        if ($this->trustTenantEmail && $token->getToken() instanceof AccessToken) {
            $this->_emailMatchingProfile = $profile;
            $this->_emailMatchingToken = $token->getToken();
            $this->_emailMatchingContext = $this->_matchingContext($profile);
        }

        return $profile;
    }

    public function canMatchEmail(UserProfile $userProfile, ?User $user = null): bool
    {
        if (parent::canMatchEmail($userProfile, $user)) {
            return true;
        }

        if (!$this->trustTenantEmail || !$this->_hasSpecificTenant() || !$this->getTrustedEmailDomains()
            || $this->matchUserSource !== 'email' || $this->matchUserDestination !== 'email'
            || $userProfile->getEmailVerified() !== null || $user?->admin
            || $userProfile !== $this->_emailMatchingProfile || !$this->_emailMatchingToken
            || $this->_matchingContext($userProfile) !== $this->_emailMatchingContext) {
            return false;
        }

        // A recycled address must not replace an identity that is already linked to Craft.
        if ($user && (strcasecmp($user->email, (string)$userProfile->email) !== 0
            || SocialLogin::$plugin->getConnections()->getConnectionByUserAndProvider($user->id, $this->handle))) {
            return false;
        }

        // Returning connections never reach this check. Verify only an initial email match.
        if ($this->_canTrustEmailMatch === null) {
            $this->_canTrustEmailMatch = false;

            try {
                $this->_canTrustEmailMatch = $this->_checkTenantEmail($userProfile, $this->_emailMatchingToken);
            } catch (Throwable) {
                // Graph failures must not authorise a match or leak tokens/profile data in logs.
                SocialLogin::info('Microsoft Entra tenant email matching could not be verified.');
            }
        }

        return $this->_canTrustEmailMatch;
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['tenant'] = $this->getTenant();

        return $config;
    }

    public function getAuthorizationUrlOptions(): array
    {
        $options = parent::getAuthorizationUrlOptions();

        $options['scope'] = [
            'User.Read',
        ];

        return $options;
    }

    public function getUserProfileFields(): array
    {
        return [
            'fullName',
            'firstName',
            'lastName',
            'upn',
            'jobTitle',
            'mobilePhone',
            'businessPhone',
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
        $rules[] = [['trustTenantEmail'], 'boolean'];
        $rules[] = [['trustedEmailDomains'], function($attribute) {
            if ($this->trustTenantEmail && !$this->getTrustedEmailDomains()) {
                $this->addError($attribute, Craft::t('social-login', 'Enter at least one exact email domain. Wildcards and URLs are not allowed.'));
            }
        }, 'skipOnEmpty' => false];
        $rules[] = [['matchUserSource', 'matchUserDestination'], function($attribute) {
            if ($this->trustTenantEmail && $this->$attribute !== 'email') {
                $this->addError($attribute, Craft::t('social-login', 'Trusted tenant matching requires Email for both user matching fields.'));
            }
        }, 'skipOnEmpty' => false];
        $rules[] = [['tenant'], function($attribute) {
            $tenant = strtolower(trim((string)$this->getTenant()));

            if ($this->allowAdminRegistration && ($tenant === '' || in_array($tenant, ['common', 'organizations', 'consumers'], true))) {
                $this->addError($attribute, Craft::t('social-login', 'A specific tenant is required for administrator registration.'));
            }

            if ($this->trustTenantEmail && !$this->_hasSpecificTenant()) {
                $this->addError($attribute, Craft::t('social-login', 'Trusted email matching requires a specific organisation tenant ID or verified domain.'));
            }
        }, 'skipOnEmpty' => false];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _matchingContext(UserProfile $profile): array
    {
        return [$profile->id, $profile->email, strtolower(trim((string)$this->getTenant())), $this->getTrustedEmailDomains()];
    }

    private function _isDomain(string $domain): bool
    {
        return str_contains($domain, '.') && !str_ends_with($domain, '.')
            && filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
            && filter_var($domain, FILTER_VALIDATE_IP) === false;
    }

    private function _hasSpecificTenant(): bool
    {
        $tenant = strtolower(trim((string)$this->getTenant()));

        // The consumer directory is not an organisation even when specified by its GUID.
        if ($tenant === '9188040d-6c67-4c5b-b112-36a304b66dad') {
            return false;
        }

        return preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/', $tenant) === 1
            || $this->_isDomain($tenant);
    }

    private function _checkTenantEmail(UserProfile $profile, AccessToken $token): bool
    {
        $email = $profile->email;

        if (!is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false || !is_string($profile->id) || $profile->id === '') {
            return false;
        }

        $domain = strtolower(substr($email, strrpos($email, '@') + 1));

        if (!in_array($domain, $this->getTrustedEmailDomains(), true)) {
            return false;
        }

        $member = $this->_graphRequest('me?$select=id,mail,userPrincipalName,userType,externalUserState', $token);

        // mail alone can be edited. Require the same address as the directory sign-in name,
        // and reject guests/invitations even when their userType has been changed to Member.
        if (($member['id'] ?? null) !== $profile->id || ($member['mail'] ?? null) !== $email
            || ($member['userType'] ?? null) !== 'Member'
            || !array_key_exists('externalUserState', $member) || $member['externalUserState'] !== null
            || !is_string($member['userPrincipalName'] ?? null)
            || stripos($member['userPrincipalName'], '#EXT#') !== false
            || strcasecmp($member['userPrincipalName'], $email) !== 0) {
            return false;
        }

        // Graph authenticates the opaque access token. Do not decode it as an ID token or
        // infer tenant membership solely from the configured authorization URL.
        $response = $this->_graphRequest('organization?$select=id,verifiedDomains', $token);
        $organizations = $response['value'] ?? null;

        if (!is_array($organizations) || count($organizations) !== 1 || !is_array($organizations[0] ?? null)) {
            return false;
        }

        $organization = $organizations[0];

        if (!is_string($organization['id'] ?? null) || !is_array($organization['verifiedDomains'] ?? null)) {
            return false;
        }

        $verifiedDomains = [];

        foreach ($organization['verifiedDomains'] as $verifiedDomain) {
            if (is_array($verifiedDomain) && is_string($verifiedDomain['name'] ?? null)) {
                $verifiedDomains[] = strtolower($verifiedDomain['name']);
            }
        }

        $tenant = strtolower(trim((string)$this->getTenant()));
        $matchesTenant = $tenant === strtolower($organization['id']) || in_array($tenant, $verifiedDomains, true);

        return $matchesTenant && in_array($domain, $verifiedDomains, true);
    }

    private function _graphRequest(string $path, AccessToken $token): array
    {
        $provider = $this->getOAuthProvider();
        $request = $provider->getAuthenticatedRequest('GET', 'https://graph.microsoft.com/v1.0/' . $path, $token);
        $response = $provider->getHttpClient()->send($request, [
            'allow_redirects' => false,
            'connect_timeout' => 5,
            'timeout' => 10,
            'verify' => true,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Microsoft Graph did not confirm tenant email matching.');
        }

        $data = json_decode((string)$response->getBody(), true, 32, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new RuntimeException('Microsoft Graph returned an invalid response.');
        }

        return $data;
    }

}
