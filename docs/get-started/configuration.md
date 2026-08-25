# Configuration
Create a `social-login.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

The below shows the defaults already used by Social Login, so you don't need to add these options unless you want to modify the values.

```php
<?php

return [
    '*' => [
        'enableLogin' => true,
        'enableCpLogin' => true,
        'enableCpElevatedLogin' => false,
        'cpLoginTemplate' => '',
        'redirectUri' => null,
        'enableRegistration' => true,
        'forceActivate' => true,
        'sendActivationEmail' => true,
        'userGroups' => [],
        'populateProfile' => true,
        'providers' => [],
    ]
];
```

## Configuration Options
- `enableLogin` - Whether to enable social login for the front-end.
- `enableCpLogin` - Whether to enable social login for the control panel.
- `enableCpElevatedLogin` - Whether to show social login in Craft’s elevated-session “Confirm your identity” modal. Disabled by default because SSO cannot satisfy password elevation and may discard unsaved CP changes when redirecting to a provider. Enable only if you need the legacy behaviour. #58
- `cpLoginTemplate` - Provide a custom template to render the social login icons for the control panel. Leave empty to use the default.
- `redirectUri` - Optionally override the OAuth redirect URI for detached or multi-domain setups. This applies to all providers.
- `enableRegistration` - Whether new users should be created if they don‘t already exist in Craft.
- `forceActivate` - Whether new users should be automatically activated without verifying their email (despite your User settings).
- `sendActivationEmail` - 'Whether an activation email should be sent to the user.
- `userGroups` - Choose which user groups to assign new users to.
- `populateProfile` - Whether new users have their profile populated from providers. This can be fine-tuned with field mapping for each provider.
- `providers` - A collection of settings for a provider.

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
