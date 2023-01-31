<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Etsy as EtsyProvider;

class Etsy extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return EtsyProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'etsy';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'loginName',
            'firstName',
            'lastName',
            'thumbnail',
            'isSeller',
            'createTimestamp',
            'createdTimestamp',
            'bio',
            'gender',
            'birthMonth',
            'birthDay',
            'transactionBuyCount',
            'transactionSoldCount',
        ];
    }

}