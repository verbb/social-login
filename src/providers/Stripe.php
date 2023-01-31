<?php
namespace verbb\sociallogin\providers;

use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Stripe as StripeProvider;

class Stripe extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function getOAuthProviderClass(): string
    {
        return StripeProvider::class;
    }

    
    // Properties
    // =========================================================================

    public static string $handle = 'stripe';


    // Public Methods
    // =========================================================================

    public function getUserProfileFields(): array
    {
        return [
            'statementDescriptor',
            'displayName',
            'timezone',
            'detailsSubmitted',
            'chargesEnabled',
            'transfersEnabled',
            'currenciesSupported',
            'defaultCurrency',
            'country',
            'object',
            'businessName',
            'businessUrl',
            'supportPhone',
            'businessLogo',
            'metaData',
            'managed',
        ];
    }

}