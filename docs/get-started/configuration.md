# Configuration
Create a `social-login.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

The below shows the defaults already used by Social Login, so you don't need to add these options unless you want to modify the values.

```php
<?php

return [
    '*' => [
        'enableLogin' => true,
        'enableCpLogin' => true,
        'cpLoginTemplate' => '',
        'enableRegistration' => true,
        'userGroups' => [],
        'populateProfile' => true,
        'providers' => [],
    ]
];
```

## Configuration Options
- `enableLogin` - Whether to enable social login for the front-end.
- `enableCpLogin` - Whether to enable social login for the control panel.
- `cpLoginTemplate` - Provide a custom template to render the social login icons for the control panel. Leave empty to use the default.
- `enableRegistration` - Whether new users should be created if they don‘t already exist in Craft.
- `userGroups` - Choose which user groups to assign new users to.
- `populateProfile` - Whether new users have their profile populated from providers. This can be fine-tuned with field mapping for each provider.
- `providers` - A collection of settings for a provider.


## Provider Settings
You can set provider settings by adding the `handle` of a provider, and passing in any setting specific to that provider. Typically, this will be OAuth settings.

```php
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
        'clientId' => 'xxxxxxxxxxxx',
        'clientSecret' => 'xxxxxxxxxxxx',

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
```
