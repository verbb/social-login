<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Trello as TrelloProvider;

class Trello extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return TrelloProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'trello';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'name',
            'photo',
        ];
    }

}