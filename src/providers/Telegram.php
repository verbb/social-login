<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Telegram as TelegramProvider;

class Telegram extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return TelegramProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'telegram';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'first_name',
            'last_name',
            'username',
            'avatar',
        ];
    }

}