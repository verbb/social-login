# Microsoft
Follow the below steps to connect to the Microsoft API.

:::tip
Choose the **Microsoft Entra** login provider for tenant email matching. **Azure** is also available for existing integrations; the **Microsoft** provider is for legacy use only.
:::

### Connect to the Microsoft/Azure API
1. Go to the <a href="https://portal.azure.com/" target="_blank">Azure Portal</a> and login to your account.
1. Navigate to **App registrations**.
1. Click the **Register an application** button.
1. Fill in the details, and for the **Redirect URI** field, select **Web** and, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Register** button.
1. On the **Overview** page copy the **Application ID** from Microsoft and paste in the **Client ID** field in Social Login.
1. Navigate to **Certificates & secrets** → **Client secrets**.
1. Click the **New client secret** button.
1. Copy the **Value** from Microsoft and paste in the **Client Secret** field in Social Login.

## Microsoft Entra Tenant Email Matching

The **Microsoft Entra** provider can match an employee signing in for the first time to an existing, non-administrator Craft account. This requires an explicit trust decision because the integration receives an email address from Microsoft Graph without confirmation of mailbox ownership.

Enable this only for an organisation whose administrators you trust to grant access to matching Craft accounts. They control member sign-in names and account provisioning. Review who can create or rename members, and avoid reassigning former employees’ addresses while their Craft accounts remain available for initial matching.

1. Open **Social Login → Providers → Microsoft Entra** and configure the app’s client ID and secret. Use an app registration restricted to your organisation’s directory.
1. Set **Tenant** to the directory’s tenant ID (GUID, recommended) or a verified domain such as `example.onmicrosoft.com`. `common`, `organizations`, and `consumers` cannot be used for this option.
1. Enable **Trust Tenant for Email Matching**.
1. Enter **Trusted Email Domains**, such as `example.com, example.org`. Each domain must also appear in the tenant’s verified domains. Wildcards are not accepted, and subdomains must be listed separately.
1. Keep both matching fields set to **Email**. When registration is enabled, these appear under **Advanced → Match Existing User**. If you previously changed the matching fields and registration is disabled, use the PHP configuration below to set them. Save the provider.

For example, a member whose email and sign-in name are both `alex@example.com` can match the existing Craft account with that email when `example.com` is allowed and verified by the configured tenant. Social Login checks the member and organisation through Microsoft Graph using the existing `User.Read` permission, which provides access to the [organisation ID and verified domains](https://learn.microsoft.com/en-us/graph/api/organization-list?view=graph-rest-1.0). An address stored only in the member’s email field, such as an alias different from their sign-in name, does not qualify.

Guest accounts, invited external members, administrator Craft accounts, and Craft accounts already connected to another Entra identity are excluded from this exception. Those users can sign in through an existing Craft login method, open **My Account → Profile**, and click **Connect** beside Microsoft Entra. Site account pages can also provide [connection controls](docs:template-guides/managing-account-connections). If Graph cannot confirm the tenant, member, or domain, automatic email matching is refused.

The option is disabled by default. It applies only to the initial email match: it does not mark emails as verified, enable forced activation, or permit email profile synchronisation. Subsequent sign-ins use the saved provider identity. Disabling the option stops new matches but does not revoke existing connections; disconnect or suspend users in Craft when removing their access.

You can also configure it in `config/social-login.php`. Omit unrelated settings so they retain their existing values:

```php
<?php

return [
    'providers' => [
        'microsoftEntra' => [
            'tenant' => '$ENTRA_TENANT_ID',
            'trustTenantEmail' => true,
            'trustedEmailDomains' => 'example.com',
            'matchUserSource' => 'email',
            'matchUserDestination' => 'email',
        ],
    ],
];
```

Set `ENTRA_TENANT_ID` in your environment to the intended organisation’s tenant ID. Test with a non-administrator Craft account that has no Entra connection, then confirm subsequent sign-ins continue to reach that same account. An alias that differs from the sign-in name or a domain outside the list should not create an automatic match.
