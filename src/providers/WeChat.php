<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\WeChat as WeChatProvider;

class WeChat extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return WeChatProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'weChat';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'nickname',
            'sex',
            'province',
            'city',
            'country',
            'headImgUrl',
            'privilege',
            'unionId',
        ];
    }

}