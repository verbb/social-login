# Displaying Login Options

Offer providers that are enabled for the relevant login surface. A provider being configured does not automatically make it available for front-end or control-panel login.

A front-end login page usually loops through the login providers and links each one to its login URL. Place this in your login template, alongside or instead of Craft's username and password form.

```twig
{% set error = craft.socialLogin.getError() %}

{% if error %}
    <p class="error">{{ error }}</p>
{% endif %}

{% for provider in craft.socialLogin.getLoginProviders() %}
    <a href="{{ craft.socialLogin.getLoginUrl(provider.handle) }}">Sign in with {{ provider.name }}</a>
{% else %}
    <p>Social sign-in isn't available right now.</p>
{% endfor %}
```

Each link sends the visitor to the provider to approve access, then back to Social Login, which signs them in or registers a new account. If something goes wrong, they return to the login page and the error is shown above the links. [Login Form](docs:template-guides/login-form) has more examples, and [Managing Account Connections](docs:template-guides/managing-account-connections) covers the messages Social Login sets.

## Listing Providers

`craft.socialLogin.getProviders()` returns an array of every [Provider](docs:reference/provider) object, including disabled providers. It takes no arguments.

`craft.socialLogin.getEnabledProviders()` returns an array of the enabled [Provider](docs:reference/provider) objects. It takes no arguments. Enabled providers can be used to connect accounts, but they only appear on login pages when login is also turned on for them, so use `getLoginProviders()` for a login page.

```twig
{# Providers a signed-in user can connect to their account #}
{% for provider in craft.socialLogin.getEnabledProviders() %}
    <p>{{ provider.name }}</p>
{% endfor %}
```

`craft.socialLogin.getLoginProviders()` returns an array of the [Provider](docs:reference/provider) objects that are enabled, support login and have front-end login turned on. It takes no arguments. Use it to build a front-end login page.

The links only work while **Enable Social Login** is turned on in the plugin settings. When it's off, `getLoginUrl()` returns `null` for every provider, so check the URL before linking if you can't be sure of the setting.

`craft.socialLogin.getCpLoginProviders()` returns an array of the [Provider](docs:reference/provider) objects that are enabled, support login and have control panel login turned on. It takes no arguments. Social Login adds these to Craft's own control panel login screen, so you only need this call for a custom control panel login template. These providers can only be used while **Enable Social Control Panel Login** is turned on.

## Getting One Provider

`craft.socialLogin.getProvider(handle)` returns the [Provider](docs:reference/provider) object with the given handle, or `null` if there isn't one. `handle` is required. Disabled providers are returned too, so check `provider.enabled` before offering it.

```twig
{% set google = craft.socialLogin.getProvider('google') %}

{% if google and google.enabled %}
    <a href="{{ craft.socialLogin.getLoginUrl('google') }}">Sign in with {{ google.name }}</a>
{% endif %}
```

## Building Login Links

`craft.socialLogin.getLoginUrl(handle, options)` returns the URL that starts a login with the given provider, or `null` when the provider doesn't exist or can't be used to log in from the current page. `handle` is required.

`options` is an optional hash added to the URL as query parameters. Pass `rememberMe: 1` to use Craft's remembered session duration, as if the visitor had ticked "Keep me signed in". After a successful login, the visitor returns to the page they started from. To send them somewhere else, pass a `redirect` value hashed with Craft's `hash` filter.

```twig
<a href="{{ craft.socialLogin.getLoginUrl('google', {
    rememberMe: 1,
    redirect: url('account')|hash,
}) }}">Sign in with Google</a>
```
