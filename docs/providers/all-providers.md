# All Providers
Social Login supports 60+ providers through OAuth1/OAuth2 protocols. Instructions on how to connect each provider are detailed below, along with any caveats.

## Supported Providers
Social Login integrates with the following providers:

- [Amazon](https://amazon.com)
- [Apple](https://apple.com)
- [Auth0](https://auth0.com)
- [Azure](https://azure.microsoft.com)
- [Basecamp](https://basecamp.com)
- [Bitbucket](https://bitbucket.com)
- [Box](https://box.com)
- [Buddy](https://buddy.works)
- [Deezer](https://deezer.com)
- [DeviantArt](https://deviantart.com)
- [Discord](https://discord.com)
- [Dribbble](https://dribbble.com)
- [Dropbox](https://dropbox.com)
- [Envato](https://envato.com)
- [Etsy](https://etsy.com)
- [Eventbrite](https://eventbrite.com)
- [Facebook](https://facebook.com)
- [Fitbit](https://fitbit.com)
- [Foursquare](https://foursquare.com)
- [GitHub](https://github.com)
- [GitLab](https://gitlab.com)
- [Google](https://google.com)
- [Gumroad](https://gumroad.com)
- [Harvest](https://getharest.com)
- [Heroku](https://heroku.com)
- [HubSpot](https://hubspot.com)
- [Imgur](https://imgur.com)
- [Instagram](https://instagram.com)
- [Jira](https://jira.com)
- [Line](https://line.me)
- [LinkedIn](https://linkedin.com)
- [Linode](https://linode.com)
- [Mailchimp](https://mailchimp.com)
- [Mastodon](https://mastodon.social)
- [Meetup](https://meetup.com)
- [Microsoft](https://microsoft.com)
- [Myob](https://myob.com)
- [Neon CRM](https://neoncrm.com)
- [PayPal](https://paypal.com)
- [Pinterest](https://pinterest.com)
- [Pipedrive](https://pipedrive.com)
- [Reddit](https://reddit.com)
- [Salesforce](https://salesforce.com)
- [Shopify](https://shopify.com)
- [Slack](https://slack.com)
- [Snapchat](https://snapchat.com)
- [SoundCloud](https://soundcloud.com)
- [Spotify](https://spotify.com)
- [Square](https://squareup.com)
- [StackExchange](https://stackexchange.com)
- [Strava](https://strava.com)
- [Stripe](https://stripe.com)
- [Telegram](https://telegram.org)
- [TikTok](https://tiktok.com)
- [Trello](https://trello.com)
- [Tumblr](https://tumblr.com)
- [Twitch](https://twitch.tv)
- [Twitter](https://twitter.com)
- [Unsplash](https://unsplash.com)
- [Vend](https://vend.com)
- [Vimeo](https://vimeo.com)
- [Vkontakte](https://vk.com)
- [WeChat](https://wechat.com)
- [Yahoo](https://yahoo.com)
- [Zendesk](https://zendesk.com)
- [Zoho](https://zoho.com)

The following however **do not** support being used as login or registration providers. This is due to either API limitations, or it being against the providers T&C's. These are supported by connecting to an existing Craft user account.

- Bitbucket
- Buddy
- DeviantArt
- Dribbble
- Dropbox
- Fitbit
- Imgur
- Instagram
- Mailchimp
- Mastodon
- Meetup
- Myob
- Pinterest
- Snapchat
- SoundCloud
- StackExchange
- Strava
- TikTok
- Tumblr
- Unsplash
- Vimeo
- WeChat

:::tip
Is your provider not in the list above? [Contact us](https://verbb.io/contact) to submit your interest, or look at the [Custom Provider](docs:developers/custom-provider) docs to write your own provider support.
:::

## Amazon
Follow the below steps to connect to the Amazon API.

### Connect to the Amazon API
1. Go to <a href="https://developer.amazon.com/lwa/sp/overview.html" target="_blank">Amazon</a> and login to your account.
1. If you don’t have a Security Profile yet, you’ll need to create one. You can do this by clicking on the **Create a New Security Profile** button on the left side.
1. Populate **Security Profile Name**, **Security Profile Description** and **Consent Privacy Notice URL**.
1. Click the **Save** button.
1. On the right side, under **Manage**, hover over the gear icon and select **Web Settings**.
1. Click the **Edit** button.
1. In the **Allowed Origins** field, enter your primary domain name.
1. In the **Allowed Return URLs** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Save** button.
1. Copy the **Client ID** from Amazon and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Amazon and paste in the **Client Secret** field in Social Login.



## Auth0
Follow the below steps to connect to the Auth0 API.

### Connect to the Auth0 API
1. Go to <a href="https://auth0.com/auth/login" target="_blank">Auth0</a> and login to your account.
1. Go to the **Applications** tab in the left panel and then click the **Applications** button.
1. Click the **Create application** button.
1. Select **Regular web Applications** and click the **Create** button.
1. Navigate to the **Settings** tab.
1. In the **Applications URI's** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **Client ID** from Auth0 and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Auth0 and paste in the **Client Secret** field in Social Login.
1. Navigate to the **Users** tab in the user management from the left panel.
1. Click the  **Create User** button.
1. Enter all required details and click the **Create** button.


## Azure
Follow the below steps to connect to the Azure API.

### Connect to the Azure API
1. Go to the <a href="https://portal.azure.com/" target="_blank">Azure Portal</a> and login to your account.
1. Navigate to **App registrations**.
1. Click the **Register an application** button.
1. Fill in the details, and for the **Redirect URI** field, select **Web** and, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Register** button.
1. On the **Overview** page copy the **Application ID** from Azure and paste in the **Client ID** field in Social Login.
1. Navigate to **Certificates & secrets** → **Client secrets**.
1. Click the **New client secret** button.
1. Copy the **Client Secret** from Azure and paste in the **Client Secret** field in Social Login.


## Basecamp
Follow the below steps to connect to the Basecamp API.

### Connect to the Basecamp API
1. Go to <a href="https://launchpad.37signals.com/integrations" target="_blank">Basecamp</a> and login to your account.
1. Click the **Register Now** button.
1. Enter the **Application Name** and other required fields.
1. In the **Redirect URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Register app** button.
1. Copy the **Client ID** from Basecamp and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Basecamp and paste in the **Client Secret** field in Social Login.


## Bitbucket
Follow the below steps to connect to the Bitbucket API.

### Connect to the Bitbucket API
1. Go to <a href="https://bitbucket.com" target="_blank">Bitbucket</a> and login to your account.
1. Click on your avatar from the navigation bar at the top of the screen.
1. Click on **All workspaces**, and select an appropriate workspace.
1. On the sidebar, select **Settings**.
1. On the sidebar, under **Apps and features**, select **OAuth consumers**.
1. Click the **Add consumer** button.  
1. In the **Callback URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Ensure you pick appropriate **Permissions** such as **Account - Email** and **Account - Read**.
1. Click the **Save** button.
1. Copy the **Key** from Bitbucket and paste in the **Client ID** field in Social Login.
1. Copy the **Secret** from Bitbucket and paste in the **Client Secret** field in Social Login.


## Box
Follow the below steps to connect to the Box API.

### Connect to the Box API
1. Go to <a href="https://app.box.com/developers/console" target="_blank">Box</a> and login to your account.
1. Click the **Create New App** button.
1. Select **Custom App** and click the **Next** button.
1. Select **Standard OAuth 2.0 (User Authentication)** and click the **Next** button.
1. Enter the **Application Name** and click the **Create App** button.
1. Click the **View Your App** button.
1. In the **OAuth 2.0 Redirect URI** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **Client ID** from Box and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Box and paste in the **Client Secret** field in Social Login.


## Buddy
Follow the below steps to connect to the Buddy API.

### Connect to the Buddy API
1. Go to <a href="https://app.buddy.works/my-apps" target="_blank">Buddy</a> and login to your account.
1. Click the **+** button to create a new app.
1. In the **Authorization Callback URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Add** button.
1. Copy the **Client Secret** from Buddy and paste in the **Client Secret** field in Social Login.
1. Copy the **Client ID** from Buddy and paste in the **Client ID** field in Social Login.


## Deezer
Follow the below steps to connect to the Deezer API.

### Connect to the Deezer API
1. Go to <a href="https://www.deezer.com" target="_blank">Deezer</a> and login to your account.
1. Click the **My Apps** button in the top right corner.
1. Click the **Create a new Application** button.
1. Fill in information about your application.
1. In the **Redirect URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **Application ID** from Deezer and paste in the **Client ID** field in Social Login.
1. Copy the **Secret Key** from Deezer and paste in the **Client Secret** field in Social Login.


## DeviantArt
Follow the below steps to connect to the DeviantArt API.

### Connect to the DeviantArt API
1. Go to <a href="https://www.deviantart.com/developers/apps" target="_blank">DeviantArt</a> and login to your account.
1. Click the **Register your Application** button.
1. In the **Redirect URI** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **Client ID** from DeviantArt and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from DeviantArt and paste in the **Client Secret** field in Social Login.


## Discord
Follow the below steps to connect to the Discord API.

### Connect to the Discord API
1. Go to <a href="https://discord.com/developers/applications" target="_blank">Discord</a> and login to your account.
1. Click the **New Application** button.
1. Fill the **Name** field and click the **Create** button.
1. On the left side, click on the **OAuth2** menu point in the Settings.
1. In the **Redirects** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Save Changes** button.
1. Copy the **Client ID** from Discord and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Discord and paste in the **Client Secret** field in Social Login.


## Dribbble
Follow the below steps to connect to the Dribbble API.

### Connect to the Dribbble API
1. Go to <a href="https://dribbble.com/account/applications/new" target="_blank">Dribbble</a> and login to your account.
1. Fill in the form details.
1. In the **Callback URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **Client ID** from Dribbble and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Dribbble and paste in the **Client Secret** field in Social Login.


## Dropbox
Follow the below steps to connect to the Dropbox API.

### Connect to the Dropbox API
1. Go to <a href="https://www.dropbox.com/developers/apps" target="_blank">Dropbox</a> and login to your account.
1. Click the **Create app** button.
1. Fill in the form details.
1. In the **Redirect URIs** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **App Key** from Dropbox and paste in the **Client ID** field in Social Login.
1. Copy the **App Secret** from Dropbox and paste in the **Client Secret** field in Social Login.


## Envato
Follow the below steps to connect to the Envato API.

### Connect to the Envato API
1. Go to <a href="https://build.envato.com/register " target="_blank">Envato</a> and login to your account.
1. Fill in the form details.
1. Ensure you select the following permissions:
    - View and search Envato sites
    - View the user's Envato Account username
    - View the user's email address
    - View the user's account profile details
1. In the **Confirmation URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **Client Secret** from Envato and paste in the **Client Secret** field in Social Login.
1. Copy the **OAuth Client ID** from Envato and paste in the **Client ID** field in Social Login.


## Etsy
Follow the below steps to connect to the Etsy API.

### Connect to the Etsy API
1. Go to <a href="https://www.etsy.com/developers/register" target="_blank">Etsy</a> and login to your account.
1. Click the **Create a new app** in the sidebar.
1. Fill in the form details.
1. Copy the **Keystring** from Etsy and paste in the **Client ID** field in Social Login.
1. Copy the **Shared Secret** from Etsy and paste in the **Client Secret** field in Social Login.


## Eventbrite
Follow the below steps to connect to the Eventbrite API.

### Connect to the Eventbrite API
1. Go to <a href="https://www.eventbrite.com/platform/api-keys" target="_blank">Eventbrite</a> and login to your account.
1. Navigate to **Account Settings** → **Developer Links** → **API Keys**.
1. Click the **Create API Key** button.
1. Fill in the form details.
1. In the **OAuth Redirect URI** field, enter the value from the **Redirect URI** field in Social Login.
1. Once created, click the **Show API key, client secret and tokens** button.
1. Copy the **API Key** from Eventbrite and paste in the **Client ID** field in Social Login.
1. Copy the **Private Token** from Eventbrite and paste in the **Client Secret** field in Social Login.


## Fitbit
Follow the below steps to connect to the Fitbit API.

### Connect to the Fitbit API
1. Go to <a href="https://dev.fitbit.com" target="_blank">Fitbit</a> and login to your account.
1. Navigate to **Manage** → **Register an App**.
1. In the **Callback URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Fill in the rest of the form, and click the **Register** button.
1. Copy the **Client ID** from Fitbit and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Fitbit and paste in the **Client Secret** field in Social Login.


## Foursquare
Follow the below steps to connect to the Foursquare API.

### Connect to the Foursquare API
1. Go to <a href="https://foursquare.com/login?continue=%2Fdevelopers%2Fapps" target="_blank">Foursquare</a> and login to your account.
1. Click the **Create a new app** button.
1. Fill in your app details. The **Detailed Description** should describe what you plan to do with this integration.
1. In the **Redirect URI** field, enter the value from the **Redirect URI** field in Social Login.
1. Fill in your details and click the **Save changes** button.
1. Copy the **Client ID** from Foursquare and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Foursquare and paste in the **Client Secret** field in Social Login.


## GitLab
Follow the below steps to connect to the GitLab API.

### Connect to the GitLab API
1. Go to <a href="https://gitlab.com/profile" target="_blank">GitLab</a> and login to your account.
1. Navigate to the **Applications** tab to add a new application.
1. In the **Redirect/Callback URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Save Application** button.
1. Copy the **Application ID** from GitLab and paste in the **Client ID** field in Social Login.
1. Copy the **Application Secret** from GitLab and paste in the **Client Secret** field in Social Login.


## Gumroad
Follow the below steps to connect to the Gumroad API.

### Connect to the Gumroad API
1. Go to <a href="https://gumroad.com/settings/advanced#application-form" target="_blank">Gumroad</a> and login to your account.
1. Navigate to **Settings** → **Advanced**.
1. Under **Applications**, in the **Redirect URI** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Create application** button.
1. Copy the **Application ID** from Gumroad and paste in the **Client ID** field in Social Login.
1. Copy the **Application Secret** from Gumroad and paste in the **Client Secret** field in Social Login.


## Harvest
Follow the below steps to connect to the Harvest API.

### Connect to the Harvest API
1. Go to <a href="https://id.getharvest.com/oauth2/clients/new" target="_blank">Harvest</a> and login to your account.
1. Fill in the form details.
1. In the **Redirect URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **Client ID** from Harvest and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Harvest and paste in the **Client Secret** field in Social Login.


## Heroku
Follow the below steps to connect to the Heroku API.

### Connect to the Heroku API
1. Go to <a href="https://dashboard.heroku.com/account/applications#api_clients" target="_blank">Heroku</a> and login to your account.
1. Navigate to **Account Settings** → **Applications**.
1. Click the **Register new API client** button.
1. In the **OAuth callback URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **Client ID** from Heroku and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Heroku and paste in the **Client Secret** field in Social Login.


## HubSpot
Follow the below steps to connect to the HubSpot API.

### Connect to the HubSpot API
1. Go to <a href="https://app.hubspot.com/signup/developers/" target="_blank">HubSpot</a> and login to your account.
1. Navigate to **Apps** in the main navigation bar.
1. Click the **Create App** button.
1. Fill in the form details.
1. Click the **Auth** tab.
1. In the **Redirect URLs** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Create App** button.
1. Copy the **Client ID** from HubSpot and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from HubSpot and paste in the **Client Secret** field in Social Login.


## Imgur
Follow the below steps to connect to the Imgur API.

### Connect to the Imgur API
1. Go to <a href="https://api.imgur.com/oauth2/addclient" target="_blank">Imgur</a> and login to your account.
1. Enter your **Application name**.
1. Select **OAuth 2 authorization with a callback URL** for the **Authorization type**.
1. In the **Authorization Callback URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Save** button.
1. Copy the **Client ID** from Imgur and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Imgur and paste in the **Client Secret** field in Social Login.


## Line
Follow the below steps to connect to the Line API.

### Connect to the Line API
1. Go to <a href="https://developers.line.biz/console/" target="_blank">Line</a> and login to your account.
1. Click the **Create a new provider** button.
1. Fill the **Provider name** field and click the **Create** button.
1. Under the **Channels** panel select the **Create a LINE Login channel** option.
1. Make sure **LINE Login** is selected as **Channel type**.
1. For **Provider** choose the provider from the list, that you just created.
1. Select your **Region**.
1. Add your **Channel icon**, **Channel name** and **Channel description**.
1. At the **App types** select the **Web app** option.
1. Read and consent to the **LINE Developers Agreement**, then click the **Create** button.
1. Scroll down to **OpenID Connect**, click the **Apply** button near the **Email address permission** label.
1. Fill out the form, then click the **Submit** button.
1. Scroll up to the top of the page and choose the **LINE Login** section.
1. In the **Callback URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Under your App name click the **Developing** button.
1. Go to the **Basic settings** tab.
1. Copy the **Channel ID** from Line and paste in the **Client ID** field in Social Login.
1. Copy the **Channel Secret** from Line and paste in the **Client Secret** field in Social Login.


## Linode
Follow the below steps to connect to the Linode API.

### Connect to the Linode API
1. Go to <a href="https://linode.com" target="_blank">Linode</a> and login to your account.
1. Navigate to **Settings** → **OAuth Apps**.
1. Click the **Add an OAuth App** button.
1. In the **Callback URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **Client Secret** from Linode and paste in the **Client Secret** field in Social Login.
1. Copy the **ID** from Linode and paste in the **Client ID** field in Social Login.


## Mailchimp
Follow the below steps to connect to the Mailchimp API.

### Connect to the Mailchimp API
1. Go to <a href="https://mailchimp.com" target="_blank">Mailchimp</a> and login to your account.

1. Click on your profile dropdown on the top-right of the screen, and select **Account**.
1. Click on **Extras** → **API keys**.
1. Click the **Register And Manager Your Apps** button.
1. Click the **Register An App** button.
1. Fill in the form details.
1. In the **Redirect URI** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **Client ID** from Mailchimp and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Mailchimp and paste in the **Client Secret** field in Social Login.


## Mastodon
Follow the below steps to connect to the Mastodon API.

### Connect to the Mastodon API
1. Go to <a href="https://mastodon.social/about" target="_blank">Mastodon</a> and login to your account.
1. Navigate to **Preferences** and then click the **Development** tab from the left hand side menu.
1. Click the **New Application** button.
1. Fill in the form details.
1. In the **Redirect URI** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Submit** button.
1. Copy the **Client Key** from Mastodon and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Mastodon and paste in the **Client Secret** field in Social Login.


## Meetup
Follow the below steps to connect to the Meetup API.

### Connect to the Meetup API
1. Go to <a href="https://secure.meetup.com/meetup_api/" target="_blank">Meetup</a> and login to your account.
1. Click the **OAuth Consumers** tab.
1. Click the **Create New Consumer** button, then the **Create one now** button.
1. Fill in the form details.
1. In the **Redirect URI** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **Client ID** from Meetup and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Meetup and paste in the **Client Secret** field in Social Login.


## Myob
Follow the below steps to connect to the Myob API.

### Connect to the Myob API
1. Go to <a href="https://my.myob.com.au" target="_blank">Myob</a> and login to your account.
1. Click the **Developer** tab.
    - Contact MYOB if you don't see the **Developer** tab in your account.
1. Click the **Register App** button.
1. Fill in the form details.
1. In the **Redirect URI** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Register Application** button.
1. Copy the **API Key** from Myob and paste in the **Client ID** field in Social Login.
1. Copy the **API Secret** from Myob and paste in the **Client Secret** field in Social Login.
1. Enter your **Username**, **Password** and **Company Name** for your Myob account into the fields Social Login.


## Neon CRM
Follow the below steps to connect to the Neon CRM API.

### Connect to the Neon CRM API
1. Go to <a href="https://app.neoncrm.com/np/admin" target="_blank">Neon CRM</a> and login to your account.
1. Click the **Settings** icon in the header.
1. Click the **Global Settings** link.
1. Click the **OAuth Configuration** link under **Third-Party Integrations**.
1. Copy the **client_id** from Neon CRM and paste in the **Client ID** field in Social Login.
1. Copy the **client_secret** from Neon CRM and paste in the **Client Secret** field in Social Login.
1. Click the **Settings** icon in the header.
1. Click the **Organization Profile** link.
1. Copy the **Organization ID** from Neon CRM and paste in the **Organization ID** field in Social Login.
1. Click the **Settings** icon in the header.
1. Click the **User Management** link.
1. Create a new user that is specifically used for API access.
1. Copy the **API Key** from Neon CRM and paste in the **API Key** field in Social Login.


## PayPal
Follow the below steps to connect to the PayPal API.

### Connect to the PayPal API
1. Go to your <a href="https://developer.paypal.com/developer/applications/" target="_blank">PayPal REST API</a> application settings.
1. Select either **Sandbox** or **Live** and click the **Create App** button.
1. Enter a **App Name** and select **Merchant** for the **App Type**.
1. Copy the **Client ID** from PayPal and paste in the **Client ID** field in Social Login.
1. Copy the **Secret** from PayPal and paste in the **Client Secret** field in Social Login.


## Pinterest
Follow the below steps to connect to the Pinterest API.

### Connect to the Pinterest API
1. Go to <a href="https://developers.pinterest.com/apps" target="_blank">Pinterest</a> and login to your account.
1. Click the **Create app** button.
1. Fill the form with appropriate information and click the **Create** button.
1. Copy the **App ID** from Pinterest and paste in the **Client ID** field in Social Login.
1. Copy the **App Secret** from Pinterest and paste in the **Client Secret** field in Social Login.
1. Scroll down to the **Platforms** section. 
1. In the **Redirect URIs** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Save Settings** button.


## Pipedrive
Follow the below steps to connect to the Pipedrive API.

### Connect to the Pipedrive API
1. Go to <a href="https://www.pipedrive.com/" target="_blank">Pipedrive</a> and login to your account.
1. Click on your profile dropdown on the top-right of the screen, and select **Personal Preferences**.
1. Click on the **API** tab.
1. Copy the **Your personal API token** from Pipedrive and paste in the **API Key** field in Social Login.


## Reddit
Follow the below steps to connect to the Reddit API.

### Connect to the Reddit API
1. Go to <a href="https://www.reddit.com/prefs/apps" target="_blank">Reddit</a> and login to your account.
1. Click the **are you a developer? create an app...** button.
1. In the **Redirect URI** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **Client ID** from Reddit and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Reddit and paste in the **Client Secret** field in Social Login.

## Salesforce
Follow the below steps to connect to the Salesforce API.

### Connect to the Salesforce API
1. Go to <a href="https://www.salesforce.com/" target="_blank">Salesforce</a> and login to your account.
1. In the main menu, on the top-right, click the **Settings** icon and select **Setup**.
1. In the left-hand sidebar, click on **Apps** → **App Manager**.
1. Click the **New Connected App** button.
1. Fill out all required fields.
1. In the **API (Enable OAuth Settings)** section, tick the **Enable OAuth Settings** checkbox.
    - In the **Callback URL** field, enter the value from the **Redirect URI** field in Social Login.
    - In the **Selected OAuth Scopes** field, select the following permissions from the list and click **Add** arrow button:
        - **Access and manage your data (api)**
        - **Allow access to your unique identifier (openid)**.
        - **Perform requests on your behalf at any time (refresh_token, offline_access)**.
    - These may also appear named as the following:
        - **Manage user data via APIs (api)**
        - **Access unique user identifiers (openid)**
        - **Perform requests at any time (refresh_token, offline_access)**
1. Click the **Save** button.
1. Copy the **Consumer Key** from Salesforce and paste in the **Client ID** field in Social Login.
1. Copy the **Consumer Secret** from Salesforce and paste in the **Client Secret** field in Social Login.
1. Click on the **Manage** button.
1. Click on the **Edit Policies** button.
1. In the **OAuth policies** section:
    - In the **Permitted Users** field, select **All users may self-authorize**.
    - In the **IP Relaxation** field, select **Relax IP restrictions**.
1. Click the **Save** button.


## Shopify
Follow the below steps to connect to the Shopify API.

### Connect to the Shopify API
1. Go to <a href="https://www.shopify.com/partners" target="_blank">Shopify</a> and login to your account.
1. Navigate to **Apps** in the sidebar.
1. Click the **Create App** button.
1. Click the **Create App Manually** button, and give your app a name.
1. Copy the **Client ID** from Shopify and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Shopify and paste in the **Client Secret** field in Social Login.


## Slack
Follow the below steps to connect to the Slack API.

### Connect to the Slack API
1. Go to the <a href="https://api.slack.com/apps?new_app=1" target="_blank">Slack App Center</a>.
1. Create a new app, by entering an **App Name** and **Development Slack Workspace**.
1. In the left-hand sidebar, under **Settings**, click **Basic Information**.
1. Under the **App Credentials** section, copy the **Client ID** and **Client Secret** values and paste into the fields Social Login.
1. In the left-hand sidebar, under **Features**, click **OAuth & Permissions**.
1. In the section **Redirect URLs**, click the **Add New Redirect URL** button and enter the value from the **Redirect URI** field in Social Login.
1. Then click the **Add** button, then click the **Save URLs** button.


## Snapchat
Follow the below steps to connect to the Snapchat API.

### Connect to the Snapchat API
1. Go to <a href="https://business.snapchat.com/" target="_blank">Snapchat</a> and login to your account.
1. Click the **Business Details** on the left-hand sidebar.
1. Click the **OAuth App** button.
1. Fill in the form details.
1. In the **Snap Redirect URI** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **Snap Client ID** from Snapchat and paste in the **Client ID** field in Social Login.
1. Copy the **Snap Client Secret** from Snapchat and paste in the **Client Secret** field in Social Login.


## SoundCloud
Follow the below steps to connect to the SoundCloud API.

### Connect to the SoundCloud API
1. Go to <a href="https://soundcloud.com/you/apps" target="_blank">SoundCloud</a> and login to your account.
1. Click the **Sign up for a new app** button.
1. Fill in the form details.
1. Click the **Register** button.
1. Copy the **Client ID** from SoundCloud and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from SoundCloud and paste in the **Client Secret** field in Social Login.
1. In the **Redirect URI** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Save App** button.


## Spotify
Follow the below steps to connect to the Spotify API.

### Connect to the Spotify API
1. Go to <a href="https://developer.spotify.com/dashboard/applications" target="_blank">Spotify</a> and login to your account.
1. Click the **Create new App** button.
1. Fill in all information as appropriate and click the **Next** button.
1. Copy the **Client ID** from Spotify and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Spotify and paste in the **Client Secret** field in Social Login.
1. Click the **Edit Settings** button. 
1. In the **Redirect URI** field, enter the value from the **Redirect URI** field in Social Login.


## Square
Follow the below steps to connect to the Square API.

### Connect to the Square API
1. Go to <a href="https://developer.squareup.com" target="_blank">Square</a> and login to your account.
1. Click the **Add Application** button.
1. Enter the **Application Name** and click on the **Save** button.
1. Go to the **OAuth** tab in the left section.
1. In the **Redirect URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **Application ID** from Square and paste in the **Client ID** field in Social Login.
1. Copy the **Application Secret** from Square and paste in the **Client Secret** field in Social Login.


## StackExchange
Follow the below steps to connect to the StackExchange API.

### Connect to the StackExchange API
1. Go to <a href="https://stackapps.com/apps/oauth/register" target="_blank">StackExchange</a> and login to your account.
1. Fill in the form details.
1. In the **Application Website** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Register Your Application** button.
1. Copy the **Client ID** from StackExchange and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from StackExchange and paste in the **Client Secret** field in Social Login.


## Strava
Follow the below steps to connect to the Strava API.

### Connect to the Strava API
1. Go to <a href="https://developers.strava.com" target="_blank">Strava</a> and login to your account.
1. Select **Create & Manage Your App** to create a new App.
1. In the **Authorization Callback Domain** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Create** button.
1. Copy the **Client ID** from Strava and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Strava and paste in the **Client Secret** field in Social Login.


## Stripe
Follow the below steps to connect to the Stripe API.

### Connect to the Stripe API
1. Go to your <a href="https://dashboard.stripe.com/account/apikeys" target="_blank">Stripe API Keys</a> page in your Stripe dashboard.
1. On the top-right of your screen, ensure the **Test Mode** lightswitch is in the **off** position if you wish to use Live details, or **on** if you wish to use Test details.
1. On the top-right of your screen, click **Developers**.
1. On the left-hand sidebar, click **API Keys**.
1. Copy the **Publishable Key** from Stripe and paste in the **Client ID** field in Social Login.
1. Copy the **Secret Key** from Stripe and paste in the **Client Secret** field in Social Login.


## Telegram
Follow the below steps to connect to the Telegram API.

### Connect to the Telegram API
1. You'll be creating a <a href="https://core.telegram.org/bots" target="_blank">Telegram Bot</a> in order to allow users to login.
1. Ensure you have a Telegram account, and the appropriate app for your device.
1. Message `@BotFather` on Telegram to register your bot and receive its authentication token.
1. Copy the **Access Token** from your chat and paste in the **Client ID** and **Client Secret** fields in Social Login.
1. Continue to message `@BotFather`, with `/setdomain`. Select your bot and enter your full domain name. e.g. `https://my-site.test`


## TikTok
Follow the below steps to connect to the TikTok API.

### Connect to the TikTok API
1. Go to <a href="https://developers.tiktok.com/" target="_blank">TikTok</a> and login to your account.
1. On the top right corner click on **Manage apps** then click on the **Connect an app** option.
1. Find the **Configuration** section complete all applicable fields.
1. For **Platform** enable the **Configure for Web** option then enter your website URL into the **Website URL** field.
1. Click on the **Save changes** button.
1. Click on **+Add products** on the left hand menu then add the **Login Kit** product, and press the **Done** button.
1. At the left hand menu click on the **Login Kit** option under **Products**.
1. Enter your **Terms of Service** and **Privacy Policy** URLs.
1. Enter your domain name into the **Redirect domain** field.
1. Scroll up and click on the **Save** changes button.
1. Click on the **Submit for review** button on the top right corner.
1. Provide as much detail to TikTok about the purpose of this app. It will be required for approval.
1. Press the **Submit** button.
1. Wait for your App to be approved, which may take a few days.
1. Once the **Status** is **Live in production**, you'll have access to **Client Key** and **Client Secret**
1. Copy the **Client Key** from TikTok and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from TikTok and paste in the **Client Secret** field in Social Login.


## Trello
Follow the below steps to connect to the Trello API.

### Connect to the Trello API
1. Go to the <a href="https://trello.com/app-key" target="_blank">Trello API Key</a> page.
1. Under the **Developer API Keys** heading, copy the **Key** value into the **Client ID** field in Social Login.
1. Under the **Allowed Origins** heading, enter the value from the **Redirect URI** field in Social Login into the text field under **New Allowed Origin** and hit **Submit**.
1. Under the **OAuth** heading, copy the **Secret** value into the **Client Secret** field in Social Login.


## Tumblr
Follow the below steps to connect to the Tumblr API.

### Connect to the Tumblr API
1. Go to <a href="https://www.tumblr.com/docs/en/api/v2" target="_blank">Tumblr</a> and login to your account.
1. Click the **Register an Application** button.
1. Fill in your app details.
1. In the **Default Callback URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **OAuth Consumer Key** from Tumblr and paste in the **Client ID** field in Social Login.
1. Copy the **Secret Key** from Tumblr and paste in the **Client Secret** field in Social Login.


## Twitch
Follow the below steps to connect to the Twitch API.

### Connect to the Twitch API
1. Go to <a href="https://dev.twitch.tv/console/apps/create" target="_blank">Twitch</a> and login to your account.
1. Your account must first have **Two Factor Authentication** enabled.
1. Go to your account settings, and click the **Settings & Privacy** menu item.
1. Follow the guide to enable **Two-Factor Authentication**.
1. Once completed, go back to <a href="https://dev.twitch.tv/console/apps/create" target="_blank">Twitch Developers</a>
1. Click the **Application** menu item, then click the **Register Your Application** button.
1. Fill out any required fields. For **Category**, choose **Website Integration**.
1. In the **Authorized Redirect URLs** field, enter the value from the **Redirect URI** field in Social Login.
1. Click **Create Button**.
1. Click the **Manage** button on the newly created app.
1. Copy the **Client ID** from Twitch and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Twitch and paste in the **Client Secret** field in Social Login.


## Unsplash
Follow the below steps to connect to the Unsplash API.

### Connect to the Unsplash API
1. Go to <a href="https://unsplash.com/oauth/applications" target="_blank">Unsplash</a> and login to your account.
1. Click the **New Application** button.
1. Agree to the terms and fill in the form details.
1. Copy the **Access Key** from Unsplash and paste in the **Client ID** field in Social Login.
1. Copy the **Secret key** from Unsplash and paste in the **Client Secret** field in Social Login.
1. In the **Redirect URI** field, enter the value from the **Redirect URI** field in Social Login.


## Vend
Follow the below steps to connect to the Vend API.

### Connect to the Vend API
1. Go to <a href="https://developers.vendhq.com/applications" target="_blank">Vend</a> and login to your account.
1. Navigate to **Applications**.
1. Click the **Add Application** button.
1. In the **Redirect URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Save Application** button.
1. Copy the **Client ID** from Vend and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Vend and paste in the **Client Secret** field in Social Login.
1. Copy your **Store Name** from Vend in the **Store Name** field in Social Login.


## Vimeo
Follow the below steps to connect to the Vimeo API.

### Connect to the Vimeo API
1. Go to <a href="https://developer.vimeo.com/apps" target="_blank">Vimeo</a> and login to your account.
1. Click the **Create App** button.
1. Fill the form with appropriate information and click the **Create app** button.
1. Copy the **Client ID** from Vimeo and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Vimeo and paste in the **Client Secret** field in Social Login.
1. In the **Callback URL** field, enter the value from the **Redirect URI** field in Social Login.


## Vkontakte
Follow the below steps to connect to the Vkontakte API.

### Connect to the Vkontakte API
1. Go to <a href="https://vk.com/apps?act=manage" target="_blank">Vkontakte</a> and login to your account.
1. Click the **Create app** button.
1. Enter the **Title** for your App and select **Website** as the platform. Populate all other fields as applicable.
1. Click the **Upload app** button.
1. Click **Settings** on the left-hand menu.
1. In the **Authorized redirect URI** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Save** button.
1. Copy the **App ID** from Vkontakte and paste in the **Client ID** field in Social Login.
1. Copy the **Secure Key** from Vkontakte and paste in the **Client Secret** field in Social Login.


## WeChat
Follow the below steps to connect to the WeChat API.

### Connect to the WeChat API
1. Go to <a href="https://open.weixin.qq.com/" target="_blank">WeChat</a> and login to your account.
1. Navigate to **Applications**.
1. Click the **Create a New App** button.
1. Fill in the form details.
1. Click the **Next** button.
1. In the **Authorization Callback URL** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Download** link to download the WeChat registration form.
1. Print the downloaded WeChat registration form, fill the form, save or scan the signed version. Upload this to your registration.
1. Copy the **Client ID** from WeChat and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from WeChat and paste in the **Client Secret** field in Social Login.


## Yahoo
Follow the below steps to connect to the Yahoo API.

### Connect to the Yahoo API
1. Go to <a href="https://developer.yahoo.com/apps/" target="_blank">Yahoo</a> and login to your account.
1. Click on the **Create an App** button on the top right corner.
1. Fill the **Application Name**, **Description** and **Home Page URL** fields.
1. In the **Redirect URI(s)** field, enter the value from the **Redirect URI** field in Social Login.
1. Under **OAuth Client Type** choose the **Confidential Client** option.
1. Under the **API Permissions**, select **OpenID Connect Permissions** with both **Email** and **Profile** enabled.
1. Click the **Create App** button.
1. Copy the **Client ID** from Yahoo and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Yahoo and paste in the **Client Secret** field in Social Login.


## Zendesk
Follow the below steps to connect to the Zendesk API.

### Connect to the Zendesk API
1. Go to <a href="https://www.zendesk.com/login" target="_blank">Zendesk</a> and login to your account.
1. Navigate to **Admin** → **API**.
1. Click the **OAuth Clients** tab on the **Channels/API** page.
1. Click the plus icon (+) on the right side of the client list.
1. In the **Redirect URLs** field, enter the value from the **Redirect URI** field in Social Login.
1. Click the **Save** button.
1. Copy the **Unique Identifier** from Zendesk and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Zendesk and paste in the **Client Secret** field in Social Login.


## Zoho
Follow the below steps to connect to the Zoho API.

### Connect to the Zoho API
1. Go to <a href="https://accounts.zoho.com/developerconsole" target="_blank">Zoho API Console</a> and login to your account.
1. Click the **Add Client** button.
1. Click **Server-based Applications**.
1. In the **Authorized Redirect URIs** field, enter the value from the **Redirect URI** field in Social Login.
1. Copy the **Client ID** from Zoho and paste in the **Client ID** field in Social Login.
1. Copy the **Client Secret** from Zoho and paste in the **Client Secret** field in Social Login.
