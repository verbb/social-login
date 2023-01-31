# Apple
Follow the below steps to connect to the Apple API.

:::warning
Apple provider requires active subscription for the [Apple Developer Program](https://developer.apple.com/programs) which costs 99 USD per year.
:::

### Connect to the Apple API
1. Go to <a href="https://developer.apple.com/" target="_blank">Apple Developer Portal</a> and login to your account.
1. Scroll to the section for **Membership details**.
1. Copy the **Team ID** from Apple and paste in the **Team ID** field in Social Login.
1. Under **Certificates, Identifiers and Profiles**, click **Identifiers**.
1. Click the blue plus icon at the top of the page.
1. Choose **App IDs** in this first step and **App** as the **App Type**.
1. Add a **Description** and **Bundle ID**. It's recommended to use a reverse-domain value (e.g. `io.verbb`).
1. Ensure to check the checkbox for **Sign In with Apple**.
1. Click the **Continue** button, then **Register**.
1. Create a new identifier, this time choosing **Services IDs**.
1. Add a **Description** and for the **Identifier** use the same value for the **Bundle ID** with `.client` (e.g. `io.verbb.client`).
1. Copy this value used for the **Identifier** and paste in the **Client ID** field in Social Login.
1. Click the **Continue** button, then **Register**.
1. Go back to edit the new identifier.
1. Ensure to check the checkbox for **Sign In with Apple** and click the **Configure** button.
    - Pick the associated **Primary App ID** you created earlier.
    - Enter the **Web Domain** for your site.
    - In the **Return URLs** field, enter the value from the **Redirect URI** field in Social Login.
    - Click **Save** and the **Continue** and **Register**.
1. Click **Keys** from the sidebar.
1. Click the blue plus icon to register a new key. Give your key a name, and check the **Sign In with Apple** checkbox.
1. Click the **Configure** button and select your primary App ID you created earlier.
1. Copy the **Key ID** from Apple and paste in the **Key File ID** field in Social Login.
1. Click the **Download** button (it will only be available once).
1. Copy this file to `config/social-login` in your project.
1. Select this file in the **Key File Path** field in Social Login.

### Apple Human Interface Guidelines
According to the [Apple Human Interface Guidelines](https://developer.apple.com/design/human-interface-guidelines/sign-in-with-apple/overview/buttons/), the login button needs to comply with their requirements. As rendering the login buttons are up to you, be sure to consult this guide.
