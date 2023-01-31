# Custom Provider
You can register your own Provider to add support for other social media platforms, or even extend an existing Provider.

```php
use modules\MyProvider;

use craft\events\RegisterComponentTypesEvent;
use verbb\sociallogin\services\Providers;
use yii\base\Event;

Event::on(Providers::class, Providers::EVENT_REGISTER_PROVIDER_TYPES, function(RegisterComponentTypesEvent $event) {
    $event->types[] = MyProvider::class;
});
```

## Example
Create the following class to house your Provider logic.

```php
<?php
namespace modules;

use verbb\sociallogin\base\OAuthProvider;

use League\OAuth2\Client\Provider\SomeProvider;

class MyProvider extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'My Provider';
    }

    public static function getOAuthProviderClass(): string
    {
        return SomeProvider::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'myProvider';


    // Public Methods
    // =========================================================================

    public function getPrimaryColor(): ?string
    {
        return '#000000';
    }

    public function getIcon(): ?string
    {
        return '<svg>...</svg>';
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('my-module/my-provider/settings', [
            'provider' => $this,
        ]);
    }
}
```

This is the minimum amount of implementation required for a typical provider.

Social Login providers are built around the [Auth](https://github.com/verbb/auth) which in turn in built around [league/oauth2-client](https://github.com/thephpleague/oauth2-client). You can see that the `getOAuthProviderClass()` must return a `League\OAuth2\Client\Provider\AbstractProvider` class.

### Additional Settings
You can of course extend and create as many additional settings as you like. The Apple provider is a good example of this, where we're required to send more settings than a typical OAuth2 request.

```php
use craft\helpers\App;

// Properties
// =========================================================================

public ?string $extraSetting = null;


// Public Methods
// =========================================================================

public function getExtraSetting(): ?string
{
    return App::parseEnv($this->extraSetting);
}

public function defineRules(): array
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

### Adding Profile Fields
You can add a list of available fields that users can map to from the provider. This will be different for each provider. The `id` and `email` attribute are already included by default as this is the minimum requirement to map a social media provider user profile to a Craft user. `response` is also included which is the raw response from the user profile request.

To add more fields, include them according to what they are called in the provider API.

```php
public function getUserProfileFields(): array
{
    return [
        'birthday',
        'postalCode',
    ];
}
```