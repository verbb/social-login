<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Jira as JiraProvider;

class Jira extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return JiraProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'jira';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'nickname',
        ];
    }

}