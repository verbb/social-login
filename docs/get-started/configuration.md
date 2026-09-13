# Configuration

You can customise Social Login’s settings using a PHP configuration file. This is optional: each setting has a default, so you only need to include the values you want to change.

To override a setting, create `social-login.php` in your Craft project’s `/config` directory and return an array of setting names and values. For example, the following will disable social login in the control panel:

```php
<?php

return [
    'enableCpLogin' => false,
];
```

All other settings keep their defaults. Add any further settings you want to change to the same array. The options below explain the available settings and their defaults.

## Configuration Options

::: reference
### `enableLogin`

**Type:** `bool` · **Default:** `true`

Whether to enable social login for the front-end.
:::

::: reference
### `enableCpLogin`

**Type:** `bool` · **Default:** `true`

Whether to enable social login for the control panel.
:::

::: reference
### `enableCpElevatedLogin`

**Type:** `bool` · **Default:** `false`

Whether to show social login in Craft’s elevated-session “Confirm your identity” modal. Disabled by default because SSO cannot satisfy password elevation and may discard unsaved CP changes when redirecting to a provider. Enable only if you need the legacy behaviour. #58
:::

::: reference
### `cpLoginTemplate`

**Type:** `string` · **Default:** `''`

Provide a custom template to render the social login icons for the control panel. Leave empty to use the default.
:::

::: reference
### `redirectUri`

**Type:** `string|null` · **Default:** `null`

Optionally override the OAuth redirect URI for detached or multi-domain setups. This applies to all providers.
:::

::: reference
### `enableRegistration`

**Type:** `bool` · **Default:** `true`

Whether new users should be created if they don‘t already exist in Craft.
:::

::: reference
### `forceActivate`

**Type:** `bool` · **Default:** `true`

Whether new users should be automatically activated without verifying their email (despite your User settings).
:::

::: reference
### `sendActivationEmail`

**Type:** `bool` · **Default:** `true`

'Whether an activation email should be sent to the user.
:::

::: reference
### `userGroups`

**Type:** `array` · **Default:** `[]`

Choose which user groups to assign new users to.
:::

::: reference
### `populateProfile`

**Type:** `bool` · **Default:** `true`

Whether new users have their profile populated from providers. This can be fine-tuned with field mapping for each provider.
:::

::: reference
### `providers`

**Type:** `array` · **Default:** `[]`

A collection of settings for a provider.
:::


### User Groups
A collection of User Group UIDs should be provided.

```php
'userGroups' => [
    '2a99c0a5-3066-45dc-8168-ec7572041f2e',
],
```

### Redirect URI Override
By default, Social Login will continue to use its legacy callback URI. If you need to use a different callback URI, such as for detached domains or an `/actions/...` callback, set `redirectUri` at the plugin level.

```php
'redirectUri' => 'https://craft.example.com/actions/social-login/auth/callback',
```

## Provider Settings
You can set provider settings by adding the `handle` of a provider, and passing in any setting specific to that provider. Typically, this will be OAuth settings.

```php
return [
    '*' => [
        // ...
        'providers' => [
            'facebook' => [
                'enabled' => true,
                'loginEnabled' => true,
                'cpLoginEnabled' => true,

                // Matching registration fields (provider-side, Craft-side)
                'matchUserSource' => 'email',
                'matchUserDestination' => 'email',

                // Field mapping
                'fieldMapping' => [
                    'username' => 'email',
                    'email' => 'email',
                    'field:myFieldHandle' => 'description',
                    'field:text' => 'response',
                ],

                // OAuth settings
                'clientId' => '••••••••••••••••••••••••••••',
                'clientSecret' => '••••••••••••••••••••••••••••',

                // Add in any additional OAuth scopes
                'scopes' => [
                     'user_birthday',
                 ],

                 // Add in any additional OAuth authorization options, used when redirecting
                 // to the provider to start the OAuth authorization process
                 'authorizationOptions' => [
                    'extra' => 'value',
                 ],

                 // Add in any additional provider-based fields to map from
                 // (depends on the provider API with what's available)
                 'customProfileFields' => [
                     'birthday',
                 ],
            ],
        ],
    ],
];
```
