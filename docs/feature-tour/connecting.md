# Connecting
Connecting links a provider account to someone who is already signed in to Craft. It differs from login, which signs someone into an existing account, and registration, which creates a Craft account.

For example, an existing Craft user might like to visit their account page on the front-end of the site, and connect their GitHub account. They could click a button to authenticate and link their GitHub account with their Craft user account on your site.

Once connected, you could make requests to GitHub's API for all manner of things. One example being showing the number of followers or repositories a user might have.

You can also allow your users to disconnect their provider account so it's no longer authenticated with them.

## User Accounts
One requirement for this is that a user must have a Craft user account setup. A guest must sign in or register before connecting.

:::warning
Not all providers support login or registration through their API's, so this is an alternative way to authenticate users with providers. The caveat of course being that they need a Craft user account.
:::

## Templating
Place these controls on an account page that requires a signed-in Craft user. Configure and enable the provider first. This example uses Facebook; substitute the handle of your configured provider.

::: code
```twig URL
{% if craft.socialLogin.isConnected('facebook') %}
    <a href="{{ craft.socialLogin.getDisconnectUrl('facebook') }}">Disconnect Facebook</a>
{% else %}
    <a href="{{ craft.socialLogin.getConnectUrl('facebook') }}">Connect Facebook</a>
{% endif %}
```

```twig Form
<form method="POST">
    {{ csrfInput() }}
    {{ hiddenInput('provider', 'facebook') }}

    {% if craft.socialLogin.isConnected('facebook') %}
        {{ actionInput('social-login/auth/disconnect') }}

        <button type="submit">Disconnect Facebook</button>
    {% else %}
        {{ actionInput('social-login/auth/connect') }}

        <button type="submit">Connect Facebook</button>
    {% endif %}
</form>
```
:::
