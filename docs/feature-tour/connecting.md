# Connecting
While you can use Social Login to allow new and existing users to login without the need for a Craft user account, you can also add a means for them to connect or link their provider account. 

For example, an existing Craft user might like to visit their account page on the front-end of the site, and connect their GitHub account. They could click a button to authenticate and link their GitHub account with their Craft user account on your site.

Once connected, you could make requests to GitHub's API for all manner of things. One example being showing the number of followers or repositories a user might have.

YOu can also allow your users to disconnect their provider account so it's no longer authenticated with them.

## User Accounts
One requirement for this is that a user must have a Craft user account setup. A guest cannot connect their account if one doesn't exist!

:::warning
Heads up! Not all providers support login or registration through their API's, so this is an alternative way to authenticate users with providers. The caveat of course being that they need a Craft user account.
:::

## Templating
Here's an example of how you might template a connect/disconnect button for users to link their account.

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
