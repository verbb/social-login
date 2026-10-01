# Managing Account Connections

Account connection controls belong in the signed-in user’s account area. Check whether the provider is connected before offering a connect or disconnect action, then display the operation’s feedback.

## Calls Used in This Task

### `craft.socialLogin.getConnectUrl(handle, options)`
Returns the connect URL for the provider.

### `craft.socialLogin.getDisconnectUrl(handle, options)`
Returns the action URL for a POST disconnect form. Include Craft’s CSRF input in the form; do not use this URL as a link.

### `craft.socialLogin.isConnected(handle)`
Returns the whether the provider is connected or not.

### `craft.socialLogin.getError()`
Returns any flash errors.

### `craft.socialLogin.getNotice()`
Returns any flash notices.

### `craft.socialLogin.getSuccess()`
Returns any flash successes.
