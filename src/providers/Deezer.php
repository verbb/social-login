<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Deezer as DeezerProvider;

class Deezer extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return DeezerProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'deezer';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'birthday',
            'country',
            'explicitContentLevel',
            'explicitContentLevelsAvailable',
            'firstname',
            'gender',
            'inscriptionDate',
            'lang',
            'lastname',
            'link',
            'name',
            'picture',
            'pictureSmall',
            'pictureMedium',
            'pictureBig',
            'pictureXl',
            'status',
            'tracklist',
        ];
    }

}