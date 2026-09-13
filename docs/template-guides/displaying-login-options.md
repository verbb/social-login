# Displaying Login Options

Offer providers that are enabled for the relevant login surface. A provider being configured does not automatically make it available for front-end or control-panel login.

## Calls Used in This Task

### `craft.socialLogin.getProviders()`
Returns a collection of all [Provider](docs:developers/provider) objects.

### `craft.socialLogin.getEnabledProviders()`
Returns a collection of all enabled [Provider](docs:developers/provider) objects.

### `craft.socialLogin.getLoginProviders()`
Returns a collection of all login-enabled [Provider](docs:developers/provider) objects.

### `craft.socialLogin.getCpLoginProviders()`
Returns a collection of all control panel login-enabled [Provider](docs:developers/provider) objects.

### `craft.socialLogin.getProvider(handle)`
Returns a [Provider](docs:developers/provider) for the provided handle.

### `craft.socialLogin.getLoginUrl(handle, options)`
Returns the login URL for the provider. Pass `rememberMe: 1` in `options` to use Craft’s remembered session duration.

