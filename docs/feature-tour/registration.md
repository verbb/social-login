# Registration
In addition to allowing your users to login to the _existing_ accounts with your website, what happens when the user doesn't exist? We can configure Social Login to create (register) the Craft User account if it doesn't exist. It'll then go through the regular [login](docs:feature-tour/login) process, instead creating the user account in Craft if it doesn't already exist.

This requires Craft Pro, which adds support for multiple users.

:::warning
Not all providers support authenticating a user to allow them to register on your site. This is due to provider API limitations or their T&C's for using their APIs. Instead, you can use them to [connect](docs:feature-tour/connecting) an existing Craft account.
:::

## Front-End Registration
You don't need to adjust anything to your front-end templates. When the user returns from the offsite provider, a new User element will be created and populated by their user profile. Social Login can activate and sign in the account immediately when the provider confirms ownership of the email. When email verification is unavailable, the account remains inactive and must complete Craft's activation flow.

## Control Panel Registration
Registration is allowed for the control panel, but it won't work out of the box, as new users will require the "Access the control panel" user permission. But as you can assign a user group to be assigned to new registrations, you can create one that has control panel access.

Automatic administrator registration is available only for providers that can be restricted to an organization controlled by the site. Azure and Microsoft Entra require a specific tenant rather than `common`, `organizations`, or `consumers`. Salesforce also requires the expected organization ID in its provider settings. Keep Craft's account activation requirements appropriate for the access those accounts receive.

:::danger
Please read the above carefully if you wish to enable control panel registration, so as not to compromise your install.
:::

## User Mapping
You can also populate the new Craft user's details from their social media profile. Commonly, you would pull in the user's first/last name, or even their avatar.

Any User attribute is supported (including downloading a user photo), along with many text-based custom fields.

:::warning
The bare-minimum mapping required is to map the user **email**. This is because Craft users require an email.
:::

Managing your user mapping is done via the provider settings.

## User Matching
Social Login uses the provider's permanent account ID after the first successful login or explicit connection, so a later email change at the provider does not move the connection to another Craft user.

For the first login only, the default matching rule compares a provider-verified email with the Craft user email. You can instead configure the provider's stable ID as the source and a Craft attribute or custom field as the destination. Other provider profile fields cannot automatically select an existing Craft account; the user must sign in locally and use [Connecting](docs:feature-tour/connecting).

## User Groups
You can also select any default User Groups newly registered users should be included to. This is in addition to the Craft **Default User Group** user setting.

## Test a New Account

Use a provider account whose email does not already belong to a Craft user. Follow the login link from a logged-out browser, approve the provider request and return to the site. Check the new user in Craft: its email, mapped fields, group membership and activation status should match your settings.

Sign out and repeat with the same provider account. This should find the existing user rather than create another one. Test account connection separately while signed in, using [Connecting](docs:feature-tour/connecting); it should attach the provider to that user. These checks cover three different outcomes even though all three may visit the provider's authorisation screen.
