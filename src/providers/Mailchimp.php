<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Mailchimp as MailchimpProvider;

class Mailchimp extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return false;
    }

    public static function getOAuthProviderClass(): string
    {
        return MailchimpProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'mailchimp';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'accountId',
            'accountName',
            'accountEmail',
            'role',
        ];
    }

}