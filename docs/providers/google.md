# Google
Follow the below steps to connect to the Google API.

### Connect to the Google API
1. Go to the <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google API Console</a>.
1. Select an existing project or create a new one.
1. If you have already setup your **OAuth Consent Screen**, you can skip this step.
    1. Go to the **API & Services** → **OAuth Consent Screen**.
    1. Select **External** for the **User Type** setting and click the **Create** button.
    1. Fill in the mandatory fields as applicable and click the **Save and continue** button.
    1. Leave the **Scopes** blank, and click the **Save and continue** button.
1. Next, go to the **APIs & Services** → **Credentials** section.
1. Click **Create Credentials** → **OAuth client ID**.
    1. On the following page, select the **Application Type** as **Web application**.
    1. Provide a suitable **Name** so you can identify it in your Google account. This is not required by {plugin}.
    1. Under the **Authorized JavaScript origins**, click **Add URI** and enter your project's Site URL.
    1. Under the **Authorized redirect URIs**, click **Add URI** and enter the value from the **Redirect URI** field in Social Login.
    1. Then click the **Create** button.
1. Once created, a popup will appear with your OAuth credentials. Copy the **Client ID** and **Client Secret** values and paste into the fields below.
1. Navigate to **OAuth Consent Screen** in the left-hand sidebar.
1. Click the **Publish App** button and **Confirm**.

## Local Testing Proxy
Google requires your Craft install to be on a public domain with SSL enabled. However, you can test out login functionality by using the **Proxy Redirect URI** setting. What this does is modify the URL for the redirect to Verbb servers, to redirect back to your install.

For example, you might have a Redirect URI like the following:

```
http://my-site.test/social-login/auth/callback
```

Using this URL for Google won't work, as it'll detect `.test` is a non-public domain name. Using the Proxy Redirect URI will change the redirect URL to be:

```
https://formie.verbb.io?return=http://my-site.test/social-login/auth/callback
```

Here, it routes the request through to our Verbb servers, which forwards on the request to the URL in the `return` parameter (which would be your local project).
