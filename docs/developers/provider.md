# Provider
Whenever you're dealing with a provider in your template, you're actually working with a `Provider` object.

## Attributes

Attribute | Description
--- | ---
`name` | The name of the provider.
`handle` | The handle of the provider.
`enabled` | Whether the provider is enabled.
`primaryColor` | The primary brand color of the provider.
`icon` | The SVG icon of the provider.
`loginEnabled` | Whether the provider is enabled for login.
`cpLoginEnabled` | Whether the provider is enabled for control panel login.
`matchUserSource` | The handle of the field (from the provider API) to use when matching an existing Craft user.
`matchUserDestination` | The handle of the field (in Craft) to use when matching an existing Craft user.
`fieldMapping` | A definition for how provider API fields should map to Craft user fields.
`authorizationOptions` | A collection of options use in the Authorization URL.
`scopes` | A collection of scopes use in the Authorization URL.
`customProfileFields` | A collection of custom fields to be included alongside default ones.

## Methods

Method | Description
--- | ---
`getToken()` | Returns the OAuth access token.


## OAuth Provider
Most, if not all providers use the [Auth](https://github.com/verbb/auth) plugin to handle the bulk of authentication work. As such, they inherit those classes.

## Attributes

Attribute | Description
--- | ---
`clientId` | The OAuth client ID.
`clientSecret` | The OAuth client secret.

## Methods

Method | Description
--- | ---
`getOAuthVersion()` | The OAuth version (1 or 2).
`getIsOAuth1()` | Whether is OAuth 1.
`getIsOAuth2()` | Whether is OAuth 2.
`getOAuthProviderConfig()` | Returns an array of config for the OAuth provider.
`getOAuthProvider()` | Returns the OAuth provider.
`getAuthorizationUrlOptions()` | The array of options for the Authorization URL.
`getAuthorizationUrl()` | The Authorization URL used to redirect to the provider.
`getAccessToken()` | Fetches the access token from the provider upon callback.
`request(method, uri, options)` | Returns the result from an authenticated API request.
