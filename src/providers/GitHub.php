<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\GitHub as GitHubProvider;

class GitHub extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return GitHubProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'gitHub';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'nickname',
            'url',
            'domain',
            'login',
            'avatar_url',
            'company',
            'type',
            'blog',
            'location',
            'hireable',
            'bio',
            'twitter_username',
            'public_repos',
            'public_gists',
            'followers',
            'following',
            'created_at',
        ];
    }

}