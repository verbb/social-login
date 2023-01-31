# Instagram
Follow the below steps to connect to the Instagram API.

### Connect to the Instagram API
1. Go to the <a href="https://developers.facebook.com/apps/" target="_blank">Meta for Developers</a> page.
1. Click the **Create App** button.
1. Select **None** as the **App Type**, and fill in the rest of the details.
1. Once created, in the left-hand sidebar, click the **Add Product** button and select **Instagram Basic Display**.
1. At the bottom of the page, click the **Create New App** button.
1. Enter the name of your new Facebook app, and click the **Click Create App** button.
1. For the **Valid OAuth Redirect URIs** setting, enter the value from the **Redirect URI** field in Social Login.
1. For the **Deauthorize Callback URIs** setting, enter the value from the **Redirect URI** field in Social Login.
1. For the **Data Deletion Request Callback URL** setting, enter the value from the **Redirect URI** field in Social Login.
1. Navigate to **App Roles** → **Roles** in the left-hand sidebar.
1. Under the **Instagram Testers** section, click the **Add Instagram Testers** button.
1. Provide your Instagram account’s username(s).
1. Click the **Submit** button to send the invitation.
    - Go to <a href="https://instagram.com/" target="_blank">Instagram</a> and login to the account you just invited.
    - Navigate to **(Profile Icon)** → **Edit Profile** → **Apps and Websites**.
    - Under the **Tester Invites** tab, accept the invitation.
1. Navigate to **App Review** → **Requests**.
1. Switch the **App Mode** toggle to **Live**.
1. Click **Request Permissions or Features**.
1. Locate the **email** permission and click the **Get advanced access** button.
1. Navigate to **Settings* → **Basic**.
1. Copy the **App ID** from Instagram and paste in the **Client ID** field in Social Login.
1. Copy the **App Secret** from Instagram and paste in the **Client Secret** field in Social Login.

### Login and Registration
The [Instagram Basic Display API](https://developers.facebook.com/docs/instagram-basic-display-api) does not allow users to use this API to login or register an accout. In their own words:

```
Instagram Basic Display is not an authentication solution. Data returned by the API cannot be used to authenticate your app users or log them into your app. If you need an authentication solution we recommend using Facebook Login instead.
```

Instagram can still be used to [connect](docs:feature-tour/connecting) user accounts, but it requires an existing Craft user to link it to.