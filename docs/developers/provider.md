# Provider
Whenever you're dealing with a provider in your template, you're actually working with a `Provider` object.

<span id="attributes"></span>

## Properties

::: reference
### `name`

**Type:** `string`

The name of the provider.
:::

::: reference
### `handle`

**Type:** `string`

The handle of the provider.
:::

::: reference
### `enabled`

**Type:** `bool`

Whether the provider is enabled.
:::

::: reference
### `primaryColor`

**Type:** `string|null`

The primary brand color of the provider.
:::

::: reference
### `icon`

**Type:** `string|null`

The SVG icon of the provider.
:::

::: reference
### `loginEnabled`

**Type:** `bool`

Whether the provider is enabled for login.
:::

::: reference
### `cpLoginEnabled`

**Type:** `bool`

Whether the provider is enabled for control panel login.
:::

::: reference
### `matchUserSource`

**Type:** `string`

The handle of the field (from the provider API) to use when matching an existing Craft user.

Automatic first-time matching accepts `email` when the provider confirms that address, or `id` for the provider's stable account identifier. After the connection is created, Social Login uses the stored provider identifier rather than repeating profile-field matching.
:::

::: reference
### `matchUserDestination`

**Type:** `string`

The handle of the field (in Craft) to use when matching an existing Craft user.

Provider ID matching is case-sensitive and exact. Verified email matching follows Craft's normal email comparison. Either match must resolve to one Craft user. If the provider cannot confirm an email, have the user sign in locally and connect the provider instead.
:::

::: reference
### `fieldMapping`

**Type:** `array`

A definition for how provider API fields should map to Craft user fields.
:::

::: reference
### `authorizationOptions`

**Type:** `array`

A collection of options use in the Authorization URL.
:::

::: reference
### `scopes`

**Type:** `array`

A collection of scopes use in the Authorization URL.
:::

::: reference
### `customProfileFields`

**Type:** `array`

A collection of custom fields to be included alongside default ones.
:::


## Methods

::: reference
### `getToken()`

Returns the OAuth access token.
:::



## OAuth Provider
Most, if not all providers use the [Auth](https://github.com/verbb/auth) plugin to handle the bulk of authentication work. As such, they inherit those classes.

<span id="attributes"></span>

## Properties

::: reference
### `clientId`

The OAuth client ID.
:::

::: reference
### `clientSecret`

The OAuth client secret.
:::


## Methods

::: reference
### `getOAuthVersion()`

The OAuth version (1 or 2).
:::

::: reference
### `getIsOAuth1()`

Whether is OAuth 1.
:::

::: reference
### `getIsOAuth2()`

Whether is OAuth 2.
:::

::: reference
### `getOAuthProviderConfig()`

Returns an array of config for the OAuth provider.
:::

::: reference
### `getOAuthProvider()`

Returns the OAuth provider.
:::

::: reference
### `getAuthorizationUrlOptions()`

The array of options for the Authorization URL.
:::

::: reference
### `getAuthorizationUrl()`

The Authorization URL used to redirect to the provider.
:::

::: reference
### `getAccessToken()`

Fetches the access token from the provider upon callback.
:::

::: reference
### `request(method, uri, options)`

Returns the result from an authenticated API request.
:::
