# Provider

A provider object represents one of the services people can log in with, such as Google or GitHub. You get provider objects from calls like `craft.socialLogin.getLoginProviders()`, which returns an array of them, or `craft.socialLogin.getProvider('google')` for one. [Displaying Login Options](docs:template-guides/displaying-login-options) shows them in use.

```twig
{% for provider in craft.socialLogin.getLoginProviders() %}
    <a href="{{ craft.socialLogin.getLoginUrl(provider.handle) }}" style="background: {{ provider.primaryColor }}">
        {{ provider.icon | raw }}
        Log in with {{ provider.name }}
    </a>
{% endfor %}
```

<span id="attributes"></span>

## Properties

::: reference
### `allowAdminRegistration`

**Type:** `bool`

Whether a person signing in with this provider can be registered as a Craft admin. Only providers that support it, such as Microsoft Entra and Salesforce, use this setting.
:::

::: reference
### `authorizationOptions`

**Type:** `array`

The extra options added to the provider’s authorisation URL, as set in your configuration file.
:::

::: reference
### `cpLoginEnabled`

**Type:** `bool`

Whether this provider is offered on the control panel login screen. Control panel login must also be enabled in the plugin settings.
:::

::: reference
### `customProfileFields`

**Type:** `array`

The extra provider profile fields to offer for field mapping, alongside the provider’s defaults.
:::

::: reference
### `enabled`

**Type:** `bool`

Whether this provider is enabled.
:::

::: reference
### `fieldMapping`

**Type:** `array`

The mapping between provider profile fields and Craft user fields, used when a user is registered or their profile is synced.
:::

::: reference
### `handle`

**Type:** `string`

The provider’s handle, such as `google`. Use it with calls like `craft.socialLogin.getLoginUrl(handle)`. This reads `getHandle()`.
:::

::: reference
### `icon`

**Type:** `string|null`

The provider’s SVG icon markup, or `null` when the provider has no icon. Output it with the `raw` filter. This reads `getIcon()`.
:::

::: reference
### `loginEnabled`

**Type:** `bool`

Whether this provider is offered for front-end login. Front-end login must also be enabled in the plugin settings.
:::

::: reference
### `matchUserDestination`

**Type:** `string`

The Craft user attribute matched against the provider value when linking an existing user, such as `email`.

Provider ID matching is case-sensitive and exact. When matching a provider email to Craft's `email` attribute, the addresses must also have the same spelling, allowing ASCII letter-case differences. Social Login trims surrounding whitespace from the provider email but does not convert internationalised domain names or normalise Unicode representations. Either match must resolve to one Craft user. If automatic matching is unavailable, have the user sign in locally and connect the provider instead.
:::

::: reference
### `matchUserSource`

**Type:** `string`

The provider profile field used to find an existing Craft user, either `email` or `id`.

Automatic first-time matching accepts `email` when the provider confirms that address, or `id` for the provider's stable account identifier. After the connection is created, Social Login uses the stored provider identifier rather than repeating profile-field matching.
:::

::: reference
### `name`

**Type:** `string`

The provider’s display name, such as `Google`. This reads `getName()`.
:::

::: reference
### `primaryColor`

**Type:** `string|null`

The provider’s brand colour as a hex value, or `null` when it has none. Use it to style a login button. This reads `getPrimaryColor()`.
:::

::: reference
### `scopes`

**Type:** `array`

The extra OAuth scopes requested alongside the provider’s default scopes.
:::

## Methods

::: reference
### `canLogin()`

**Arguments:** `isCpRequest` (`bool`), whether the check is for the control panel login screen.

**Returns:** `bool`

Returns whether this provider can be used to log in right now, taking into account whether it’s enabled and the relevant login settings.
:::

::: reference
### `getToken()`

**Returns:** `verbb\auth\models\Token|null`

Returns the access token from the current user’s connection to this provider, or `null` when no one is logged in or the current user hasn’t connected it.
:::

::: reference
### `isConfigured()`

**Returns:** `bool`

Returns whether the provider has the settings it needs, such as a client ID and secret for an OAuth provider.
:::

::: reference
### `isConnected()`

**Returns:** `bool`

Returns whether the current user has connected their account with this provider. It returns `false` when no one is logged in.
:::

## OAuth Providers

Most providers use the [Auth](https://github.com/verbb/auth) module to handle OAuth, and extend `verbb\sociallogin\base\OAuthProvider`. They have these properties and methods as well.

### OAuth Provider Properties

::: reference
### `clientId`

**Type:** `string|null`

The OAuth client ID, which can be an environment variable reference such as `$GOOGLE_CLIENT_ID`. `getClientId()` returns the parsed value.
:::

::: reference
### `clientSecret`

**Type:** `string|null`

The OAuth client secret, which can be an environment variable reference. `getClientSecret()` returns the parsed value.
:::

### OAuth Provider Methods

::: reference
### `getAccessToken()`

**Returns:** `League\OAuth1\Client\Credentials\TokenCredentials|League\OAuth2\Client\Token\AccessToken|null`

Returns the access token fetched from the provider during the OAuth callback, or `null` when the callback didn’t supply one.
:::

::: reference
### `getAuthorizationUrl()`

**Returns:** `string|null`

Returns the provider URL that a user is redirected to when they start logging in or connecting.
:::

::: reference
### `getAuthorizationUrlOptions()`

**Returns:** `array`

Returns the options added to the authorisation URL, including the combined default and configured scopes.
:::

::: reference
### `getIsOAuth1()`

**Returns:** `bool`

Returns whether the provider uses OAuth 1.
:::

::: reference
### `getIsOAuth2()`

**Returns:** `bool`

Returns whether the provider uses OAuth 2.
:::

::: reference
### `getOAuthProvider()`

**Returns:** `League\OAuth1\Client\Server\Server|League\OAuth2\Client\Provider\AbstractProvider`

Returns the underlying OAuth client from the [Auth](https://github.com/verbb/auth) module, created from `getOAuthProviderConfig()`.
:::

::: reference
### `getOAuthProviderConfig()`

**Returns:** `array`

Returns the configuration used to create the OAuth client, such as the client ID, secret and redirect URI.
:::

::: reference
### `getOAuthVersion()`

**Returns:** `int`

Returns the OAuth version the provider uses, either `1` or `2`.
:::

::: reference
### `request()`

**Arguments:** `method` (`string`, default `GET`), `uri` (`string`) and `options` (`array`, Guzzle request options).

**Returns:** `mixed`

Returns the decoded response of an authenticated API request to the provider, using the current user’s token. It throws an exception when there’s no stored token.
:::
