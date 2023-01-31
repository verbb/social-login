<?php
namespace verbb\sociallogin\base;

use verbb\sociallogin\SocialLogin;

use Craft;
use craft\helpers\UrlHelper;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\base\OAuthProviderTrait;
use verbb\auth\models\Token;
use verbb\auth\models\UserProfile;

abstract class OAuthProvider extends Provider implements OAuthProviderInterface
{
    // Traits
    // =========================================================================

    use OAuthProviderTrait;
    

    // Public Methods
    // =========================================================================

    public function settingsAttributes(): array
    {
        // These won't be picked up in a Trait
        $attributes = parent::settingsAttributes();
        $attributes[] = 'clientId';
        $attributes[] = 'clientSecret';

        return $attributes;
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            ['clientId', 'clientSecret'], 'required', 'when' => function($model) {
                return $model->enabled;
            },
        ];

        return $rules;
    }

    public function isConfigured(): bool
    {
        return $this->clientId && $this->clientSecret;
    }

    public function getRedirectUri(): ?string
    {
        return UrlHelper::siteUrl('social-login/auth/callback');
    }

    public function getAuthorizationUrlOptions(): array
    {
        // Use any auth options defined in config files
        $options = $this->authorizationOptions;

        // If we have any custom-defined scopes, merge with the default provider
        if ($this->scopes) {
            $defaultScopes = $this->getOAuthProvider()->getDefaultScopes();

            $options['scope'] = array_unique(array_merge($defaultScopes, $this->scopes));
        }

        return $options;
    }

    public function getUserProfile(Token $token): UserProfile
    {
        if ($this->getIsOAuth1()) {
            $resource = $this->getOAuthProvider()->getUserDetails($token->getToken());
        } else {
            $resource = $this->getOAuthProvider()->getResourceOwner($token->getToken());
        }

        return new UserProfile($resource);
    }

    public function getToken(): ?Token
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($connection = SocialLogin::$plugin->getConnections()->getConnectionByUserAndProvider($currentUser->id, $this->handle)) {
            return $connection->getToken();
        }

        return null; 
    }
}