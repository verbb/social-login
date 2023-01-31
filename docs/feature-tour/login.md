# Login
Rather than requiring your users to register a Craft user account, you can offer them to login with their existing account with a social media provider. It's a pretty common pattern these days to allow users to login to a website with their Facebook, Apple, Twitter or Google account. Social Login makes this a breeze.

:::warning
Not all providers support authenticating a user to allow them to login on your site. This is due to provider API limitations or their T&C's for using their APIs. Instead, you can use them to [connect](docs:feature=tour/connecting) an existing Craft account.
:::

## Front-end Login
Adding a social login to the front-end of your site couldn't be easier!

::: code
```twig URL
<a href="{{ craft.socialLogin.getLoginUrl('facebook') }}">Login to Facebook</a>
```

```twig Form
<form method="POST">
    {{ csrfInput() }}
    {{ actionInput('social-login/auth/login') }}
    {{ hiddenInput('provider', 'facebook') }}

    <button type="submit">Login to Facebook</button>
</form>
```
:::

Here, this will output a link (or a form) that when clicked, will redirect away from your website to the provider's website to login there. Their login details are never shared or known to your website.

Once authenticated with the provider, they'll be returned back to your website. If there's a Craft user account that matches the email with the remote provider account, then they'll automatically be logged in.

:::tip
What if the user doesn't have an account already? [User Registration](docs:feature-tour/registration) can help with that.
:::

### Redirection
When returning to your website after being redirected to the providers site, Social Login will use the referrer URL to redirect the user back to. This will be the page that the user was on when they clicked the "login" button.

You can check this behaviour if you require, to specify where to redirect the user once logged in on the remote provider website.

::: code
```twig URL
<a href="{{ craft.socialLogin.getLoginUrl('facebook', { redirect: '/my-account' | hash }) }}">Login to Facebook</a>
```

```twig Form
<form method="POST">
    {{ csrfInput() }}
    {{ actionInput('social-login/auth/login') }}
    {{ hiddenInput('provider', 'facebook') }}
    {{ redirectInput('/my-account') }}

    <button type="submit">Login to Facebook</button>
</form>
```
:::


## Control Panel Login
You can allow your users to login to the control panel of Craft if you like! Buttons will be added below the main control panel login form as an alternative for users to login. Of course, these users will require user permissions to be able to access the control panel as well.

They'll also be added to the "session ended" modal login form that pops up after a certain number of minutes of inactivity.

### Template
Social Login will create the buttons to login for you, but you also have full control over how the buttons look. Set the **Control Panel Login Template** setting to a template in your `templates` folder, and go for it. Here's a quick example:

```twig
{# Fetch all providers that have control panel login enabled #}
{% set providers = craft.socialLogin.getCpLoginProviders() %}

{% if providers %}
    {% for provider in providers %}
        <button type="button" data-social-provider="{{ provider.handle }}">
            {{ provider.icon | raw }} Sign in with {{ provider.name }}
        </button>
    {% endfor %}
{% endif %}
```

The only requirement for your templates is the inclusion of `<button>` elements (for the user to click) and these must have a `data-social-provider` attribute, with the handle of the provider. You're otherwise free to template whatever suits your needs.
