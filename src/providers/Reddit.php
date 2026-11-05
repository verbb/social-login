<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\models\Token;
use verbb\auth\models\UserProfile;
use verbb\auth\providers\Reddit as RedditProvider;

use League\OAuth2\Client\Provider\GenericResourceOwner;

class Reddit extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return RedditProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'reddit';


    // Public Methods
    // =========================================================================

    public function getUserProfile(Token $token): UserProfile
    {
        $resource = $this->getOAuthProvider()->getResourceOwner($token->getToken());

        // The Reddit client returns the raw response, rather than a resource owner
        if (is_array($resource)) {
            $resource = new GenericResourceOwner($resource, 'id');
        }

        return new UserProfile($resource);
    }

}
