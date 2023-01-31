<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Meetup as MeetupProvider;

class Meetup extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return MeetupProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'meetup';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'link',
            'name',
            'birthday',
            'photo',
            'status',
            'joined',
            'visited',
            'lang',
            'country',
            'city',
            'lat',
            'lon',
            'topics',
            'otherServices',
            'self',
        ];
    }

}