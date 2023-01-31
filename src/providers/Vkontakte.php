<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Vkontakte as VkontakteProvider;

class Vkontakte extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return VkontakteProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'vkontakte';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'birthday',
            'city',
            'country',
            'domain',
            'firstName',
            'friendStatus',
            'homeTown',
            'lastName',
            'maidenName',
            'nickname',
            'photoMax',
            'photoMaxOrig',
            'screenName',
            'sex',
        ];
    }

}