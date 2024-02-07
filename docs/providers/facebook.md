# Facebook
Follow the below steps to connect to the Facebook API.

## Connect to the Facebook API
1. Go to the <a href="https://developers.facebook.com/apps/" target="_blank">Meta for Developers</a> page.
1. Click the **Create App** button.
1. Select **Other** and **Consumer** as the app type, and fill in the rest of the details.
1. Once created, in the left-hand sidebar, click the **Add Product** button and select **Facebook Login**.
1. Select **Web** as the type and your website address into **Site URL**.
1. Navigate to **Facebook Login** section in the left-hand siderbar, click **Settings**.
1. For the **Valid OAuth Redirect URIs** setting, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Save Changes** button.
1. Navigate to **App Review** → **Requests**.
1. Switch the **App Mode** toggle to **Live**.
1. Click **Request Permissions or Features**.
1. Locate the **public_profile** permission and click the **Get advanced access** button.
1. Locate the **email** permission and click the **Get advanced access** button.
1. Navigate to **App Settings** → **Basic** item in the left-hand sidebar.
1. Enter your domain name to the **App Domains** field.
1. Fill in the **Privacy Policy** and **User Data Deletion** fields as applicable.
1. Click the **Save Changes** button.
1. Copy the **App ID** from Facebook and paste in the **Client ID** field in Social Login.
1. Copy the **App Secret** from Facebook and paste in the **Client Secret** field in Social Login.

:::tip
Ensure that you pick **Facebook Login** and not **Facebook Login for Business**, which are different products. If you must use **Facebook Login for Business**, you'll need to provide additional scopes, as per the below. 
:::

### Facebook Login for Business
If you require the use of the **Facebook Login for Business** product in your Facebook App, you may do so, but note that you'll need to supply additional scopes in your [configuration](docs:get-started/configuration).

```php
<?php

return [
    '*' => [
        // ...
        'providers' => [
            'facebook' => [
                // ...
                'scopes' => [
                    'business_management',
                ],
            ],
        ],
    ]
];
```