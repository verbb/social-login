# Requests
If you have logged in, registered or connected a social media provider with Social Login, you can also make authenticated requests to their API. This allows you to fetch content from their API's without having to handle the complexities of authentication.

For example, let's say we want to fetch some Instagram posts from our connected Instagram account.

::: code
```twig Twig
{% set provider = craft.socialLogin.getProvider('instagram') %}

{% if provider and provider.isConnected() %}
    {% set mediaItems = provider.request('GET', 'me/media', {
        query: {
            limit: '5',
            fields: 'id,caption',
        },
    }) %}

    {% for media in mediaItems %}
        {# ... #}
    {% endfor %}
{% endif %}```

```twig PHP
use verbb\sociallogin\SocialLogin;

$provider = SocialLogin::$plugin->getProviders()->getProviderByHandle('instagram');

if ($provider && $provider->isConnected()) {
    $mediaItems = $provider->request('GET', 'me/media', [
        'query' => [
            'limit' => '5',
            'fields' => 'id,caption',
        ],
    ]);

    foreach ($mediaItems as $media) {
        // ...
    }
}
```
:::

Here, we first check that we're connected to Instagram (we have a valid OAuth token), and if so, fire a `https://graph.instagram.com/me/media?fields=id,caption&limit=5` API request. With this data, we can then loop over it to output it.

The possibilities of what you can do will be entirely up to the providers API, but the `request()` method should help make interacting with these APIs easier.

Available method parameters are:

Attribute | Type | Description
--- | ---
`method` | `string` | The HTTP method to use (e.g. `GET`, `POST`, `PUT`).
`uri` | `string` | The relative endpoint to fetch. The base URL for the provider is already included.
`options` | `object` | An object of options to pass through. These could be `query` params, `headers`, or `body` params.

