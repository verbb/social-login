# Login Form
The below is an example login form for social login.

```twig
{# Display any success/notice or errors relating to login #}
{{ craft.socialLogin.getNotice() }}
{{ craft.socialLogin.getError() }}

<a href="{{ craft.socialLogin.getLoginUrl('facebook') }}">Login to Facebook</a>
```

It would also be common to list out all your enabled login providers.

```twig
{# Display any success/notice or errors relating to login #}
{{ craft.socialLogin.getNotice() }}
{{ craft.socialLogin.getError() }}

{% for provider in craft.socialLogin.getLoginProviders() %}
    <a href="{{ craft.socialLogin.getLoginUrl(provider.handle) }}">Login to {{ provider.name }}</a>
{% endfor %}
```
