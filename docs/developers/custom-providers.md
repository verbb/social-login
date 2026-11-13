# Custom Providers

A provider connects Social Login to a service people can log in with, such as Google or GitHub. Create a custom provider to support a service Social Login doesn’t include, or to change how an existing provider behaves by extending its class.

## Create the Provider Class

Social Login uses the [Auth](https://github.com/verbb/auth) module for OAuth authorisation and token handling, and each provider builds on an Auth provider, which in turn extends a [league/oauth2-client](https://github.com/thephpleague/oauth2-client) provider. Check the providers Auth includes first. If it already supports your service, extend `verbb\sociallogin\base\OAuthProvider` and return that Auth provider from `getOAuthProviderClass()`.

This class, saved as `modules/sitemodule/Example.php`, uses Auth’s Facebook provider. The static `$handle` property gives the provider its handle.

```php
<?php
namespace modules\sitemodule;

use Craft;
use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Facebook as FacebookProvider;

class Example extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Example Provider';
    }

    public static function getOAuthProviderClass(): string
    {
        return FacebookProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'example';


    // Public Methods
    // =========================================================================

    public function getPrimaryColor(): ?string
    {
        return '#000000';
    }

    public function getIcon(): ?string
    {
        return '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>';
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sitemodule/example/settings', [
            'provider' => $this,
        ]);
    }
}
```

`getSettingsHtml()` renders the provider’s settings from a template in your module, so register a control panel template root for `sitemodule` in your module. Craft’s [template root documentation](https://craftcms.com/docs/5.x/extend/template-roots.html) explains how.

## Register the Provider

Social Login finds providers through the `registerProviderTypes` event. Register your class from your module or plugin’s `init()` method.

```php
use craft\events\RegisterComponentTypesEvent;
use modules\sitemodule\Example;
use verbb\sociallogin\services\Providers;
use yii\base\Event;

Event::on(Providers::class, Providers::EVENT_REGISTER_PROVIDER_TYPES, function(RegisterComponentTypesEvent $event) {
    $event->types[] = Example::class;
});
```

## Use a Generic OAuth Provider
Every Social Login provider class needs an [Auth](https://github.com/verbb/auth) provider. If Auth doesn’t have one for your service, use its `Generic` provider. It handles the OAuth flow for you, and your class supplies the endpoint URLs, scopes and other settings.

```php
<?php
namespace modules\sitemodule;

use Craft;
use verbb\sociallogin\base\OAuthProvider;

use verbb\auth\providers\Generic as GenericProvider;

class Example1 extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Example 1 Provider';
    }

    public static function getOAuthProviderClass(): string
    {
        return GenericProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'example1';


    // Public Methods
    // =========================================================================

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['urlAuthorize'] = 'https://accounts.zoho.com/oauth/v2/auth';
        $config['urlAccessToken'] = 'https://accounts.zoho.com/oauth/v2/token';
        $config['urlResourceOwnerDetails'] = 'https://accounts.zoho.com/oauth/user/info';
        $config['scopes'] = ['aaaserver.profile.READ'];
        $config['scopeSeparator'] = ' ';

        return $config;
    }

    public function getPrimaryColor(): ?string
    {
        return '#000000';
    }

    public function getIcon(): ?string
    {
        return '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>';
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sitemodule/example-1/settings', [
            'provider' => $this,
        ]);
    }
}
```

`getOAuthProviderClass()` returns `verbb\auth\providers\Generic`, which tells Social Login which [Auth](https://github.com/verbb/auth) provider to use. `getOAuthProviderConfig()` then supplies the settings that provider is created with.

These settings follow the [league/oauth2-client](https://github.com/thephpleague/oauth2-client) configuration. The example uses the endpoints for [Zoho](https://www.zoho.com/en-au/crm/).

## Use Your Own Auth Provider
When you need more control over the authorisation flow than the `Generic` provider gives you, such as special handling of access tokens, create your own [Auth](https://github.com/verbb/auth) provider class instead.

```php
<?php
namespace modules\sitemodule;

use Craft;
use verbb\sociallogin\base\OAuthProvider;

use modules\sitemodule\auth\Example2 as Example2Provider;

class Example2 extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Example 2 Provider';
    }

    public static function getOAuthProviderClass(): string
    {
        return Example2Provider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'example2';


    // Public Methods
    // =========================================================================

    public function getPrimaryColor(): ?string
    {
        return '#000000';
    }

    public function getIcon(): ?string
    {
        return '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>';
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sitemodule/example-2/settings', [
            'provider' => $this,
        ]);
    }
}
```

This class has no `getOAuthProviderConfig()` method, and `getOAuthProviderClass()` returns your own `modules\sitemodule\auth\Example2` class, which looks like this.

```php
<?php
namespace modules\sitemodule\auth;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use verbb\auth\base\ProviderTrait;
use verbb\auth\models\Token;

class Example2 extends AbstractProvider
{
    // Traits
    // =========================================================================

    use BearerAuthorizationTrait;
    use ProviderTrait;


    // Public Methods
    // =========================================================================

    public function getBaseAuthorizationUrl(): string
    {
        return 'https://accounts.zoho.com/oauth/v2/auth';
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return 'https://accounts.zoho.com/oauth/v2/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return 'https://accounts.zoho.com/oauth/user/info';
    }

    public function getApiUrl(): string
    {
        return 'https://www.zohoapis.com';
    }

    public function getBaseApiUrl(?Token $token): ?string
    {
        return $this->getApiUrl();
    }


    // Protected Methods
    // =========================================================================

    protected function getDefaultScopes(): array
    {
        return ['aaaserver.profile.READ'];
    }

    protected function getScopeSeparator(): string
    {
        return ' ';
    }

    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (isset($data['error'])) {
            throw new IdentityProviderException(
                ($data['error']['message'] ?? $response->getReasonPhrase()),
                $response->getStatusCode(),
                $response
            );
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return null;
    }
}
```

An [Auth](https://github.com/verbb/auth) provider is a [league/oauth2-client](https://github.com/thephpleague/oauth2-client) provider that also uses `verbb\auth\base\ProviderTrait`, so anything that library supports works here. The example again uses the [Zoho](https://www.zoho.com/en-au/crm/) endpoints.

## Add Settings
Add a public property for each extra setting your provider needs, and include it in validation, `isConfigured()` and the OAuth configuration as required. The built-in Apple provider works this way, because Apple needs more settings than a typical OAuth 2 request. This is a partial class showing the members to add.

```php
use craft\helpers\App;

public ?string $extraSetting = null;

public function getExtraSetting(): ?string
{
    return App::parseEnv($this->extraSetting);
}

protected function defineRules(): array
{
    $rules = parent::defineRules();
    $rules[] = [['extraSetting'], 'required'];

    return $rules;
}

public function isConfigured(): bool
{
    return parent::isConfigured() && $this->extraSetting;
}

public function getOAuthProviderConfig(): array
{
    $config = parent::getOAuthProviderConfig();
    $config['extraSetting'] = 'someValue';

    return $config;
}
```

## Add Profile Fields
Return the provider profile fields that administrators can map to Craft user fields. These differ for each service, so use the names from the provider’s API. Social Login always includes `id` and `email`, which are the minimum needed to match a profile to a Craft user, and `response`, the raw profile response.

```php
public function getUserProfileFields(): array
{
    return [
        'birthday',
        'postalCode',
    ];
}
```

## Methods

A provider class extends `verbb\sociallogin\base\OAuthProvider` and overrides these methods as needed.

::: reference
### `displayName()`

**Returns:** `string`

Returns the provider’s name, shown in the control panel and on login buttons. This method is static and required.
:::

::: reference
### `getAuthorizationUrlOptions()`

**Returns:** `array`

Returns the options added to the authorisation URL. The default combines the Auth provider’s default scopes with the configured `scopes` and `authorizationOptions`.
:::

::: reference
### `getIcon()`

**Returns:** `string|null`

Returns the SVG markup for the provider’s icon, or `null` for no icon.
:::

::: reference
### `getOAuthProviderClass()`

**Returns:** `string`

Returns the class name of the [Auth](https://github.com/verbb/auth) provider that handles OAuth for this provider. This method is static and required.
:::

::: reference
### `getOAuthProviderConfig()`

**Returns:** `array`

Returns the configuration the Auth provider is created with. Start with `parent::getOAuthProviderConfig()`, which supplies the client ID, secret and redirect URI.
:::

::: reference
### `getPrimaryColor()`

**Returns:** `string|null`

Returns the provider’s brand colour as a hex value, or `null` for none.
:::

::: reference
### `getSettingsHtml()`

**Returns:** `string|null`

Returns the HTML for the provider’s settings in the control panel. The default renders `social-login/providers/[handle]`, so a custom provider renders its own template.
:::

::: reference
### `getUserProfileFields()`

**Returns:** `array`

Returns the names of the extra provider profile fields that can be mapped to Craft user fields.
:::

::: reference
### `isConfigured()`

**Returns:** `bool`

Returns whether the provider has the settings it needs. OAuth providers return `true` when a client ID and secret are set.
:::

::: reference
### `supportsAdminRegistration()`

**Returns:** `bool`

Returns whether a person signing in with this provider can be registered as an admin. This method is static and returns `false` by default.
:::

::: reference
### `supportsLogin()`

**Returns:** `bool`

Returns whether the provider can be used to log in, as well as to connect accounts. This method is static and returns `true` by default.
:::
