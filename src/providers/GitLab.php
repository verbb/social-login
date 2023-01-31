<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\GitLab as GitLabProvider;

class GitLab extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return GitLabProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'gitLab';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'domain',
            'name',
            'username',
            'avatarUrl',
            'profileUrl',
            'token',
        ];
    }

}