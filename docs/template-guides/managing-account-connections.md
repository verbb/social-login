# Managing Account Connections

Account connection controls belong in the signed-in user’s account area. Check whether the provider is connected before offering a connect or disconnect action, then display the operation’s feedback.

Connecting a provider links it to the user's existing Craft account, so they can log in with it later. This example belongs in an account template that only signed-in users can reach. It lists each enabled provider with a connect link or a disconnect button.

```twig
{% set error = craft.socialLogin.getError() %}
{% set notice = craft.socialLogin.getNotice() %}

{% if error %}
    <p class="error">{{ error }}</p>
{% endif %}

{% if notice %}
    <p class="notice">{{ notice }}</p>
{% endif %}

{% for provider in craft.socialLogin.getEnabledProviders() %}
    <div>
        {{ provider.name }}

        {% if craft.socialLogin.isConnected(provider.handle) %}
            <form method="post" action="{{ craft.socialLogin.getDisconnectUrl(provider.handle) }}">
                {{ csrfInput() }}
                <button type="submit">Disconnect</button>
            </form>
        {% else %}
            <a href="{{ craft.socialLogin.getConnectUrl(provider.handle) }}">Connect</a>
        {% endif %}
    </div>
{% endfor %}
```

After connecting or disconnecting, the user returns to this page with a notice such as "Google connected." or an error explaining what went wrong.

## Connecting and Disconnecting Providers

`craft.socialLogin.getConnectUrl(handle, options)` returns the URL that connects the given provider to the signed-in user's account, or `null` when the provider doesn't exist or is disabled. `handle` is required. The user must be signed in when they follow the link, otherwise they're returned with an error.

`options` is an optional hash added to the URL as query parameters. After connecting, the user returns to the page they started from. To send them somewhere else, pass a `redirect` value hashed with Craft's `hash` filter.

```twig
<a href="{{ craft.socialLogin.getConnectUrl('google', {
    redirect: url('account/connections')|hash,
}) }}">Connect Google</a>
```

`craft.socialLogin.getDisconnectUrl(handle, options)` returns the action URL for a POST disconnect form, or `null` when the provider doesn't exist or is disabled. `handle` is required, and `options` is an optional hash added to the URL as query parameters. Include Craft’s CSRF input in the form, and don't use this URL as a link.

Disconnecting removes the link between the provider and the signed-in user's account. The user then returns to the page the form was submitted from.

## Checking a Connection

`craft.socialLogin.isConnected(handle)` returns `true` when the signed-in user has connected the given provider to their account. `handle` is required. It returns `false` for guests, and for a provider that doesn't exist.

```twig
{% if craft.socialLogin.isConnected('google') %}
    <p>You can sign in with Google.</p>
{% endif %}
```

## Showing Messages

`craft.socialLogin.getError()` returns the error message from the last login, connect or disconnect attempt, or `null` if there isn't one. It takes no arguments. Messages are flash messages, so each is shown once, on the page the user returns to.

`craft.socialLogin.getNotice()` returns the notice from the last successful connect or disconnect, such as "Google connected.", or `null` if there isn't one. It takes no arguments. Social Login reports its successful outcomes as notices.

`craft.socialLogin.getSuccess()` returns a success message stored for Social Login, or `null` if there isn't one. It takes no arguments. Social Login itself sets notices and errors rather than success messages, so this is usually empty unless your own code sets one.
