# Available Variables
The following methods are available to call in your Twig templates:

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
Returns the login URL for the provider.

### `craft.socialLogin.getConnectUrl(handle, options)`
Returns the connect URL for the provider.

### `craft.socialLogin.getDisconnectUrl(handle, options)`
Returns the disconnect URL for the provider.

### `craft.socialLogin.isConnected(handle)`
Returns the whether the provider is connected or not.

### `craft.socialLogin.getError()`
Returns any flash errors.

### `craft.socialLogin.getNotice()`
Returns any flash notices.

### `craft.socialLogin.getSuccess()`
Returns any flash successes.
