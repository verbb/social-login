<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Dribbble as DribbbleProvider;

class Dribbble extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return DribbbleProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'dribbble';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'username',
            'htmlUrl',
            'avatarUrl',
            'bio',
            'location',
            'links',
            'bucketCount',
            'commentsReceivedCount',
            'followersCount',
            'followingsCount',
            'likesCount',
            'likesReceivedCount',
            'projectsCount',
            'reboundsReceivedCount',
            'shotsCount',
            'teamsCount',
            'type',
            'bucketsUrl',
            'followersUrl',
            'followingUrl',
            'likesUrl',
            'projectsUrl',
            'shotsUrl',
            'teamsUrl',
            'created',
            'updated',
        ];
    }

}