# Registration
In addition to allowing your users to login to the _existing_ accounts with your website, what happens when the user doesn't exist? We can configure Social Login to create (register) the Craft User account if it doesn't exist. It'll then go through the regular [login](docs:feature-tour/login) process, instead creating the user account in Craft if it doesn't already exist.

This requires Craft Pro, which adds support for multiple users.

:::warning
Not all providers support authenticating a user to allow them to register on your site. This is due to provider API limitations or their T&C's for using their APIs. Instead, you can use them to [connect](docs:feature-tour/connecting) an existing Craft account.
:::

## Front-end Registration
You don't need to adjust anything to your front-end templates. When the user returns from the offsite provider, they'll be auto-registered and logged into their new account. A new User element will be created, populated by their user profile from the provider.

## Control Panel Registration
Registration is allowed for the control panel, but it won't work out of the box, as new users will require the "Access the control panel" user permission. But as you can assign a user group to be assigned to new registrations, you can create one that has control panel access.

However, if you were to enable this setting, it is **highly** recommended you turn off "Force Activation" setting in Social Login, and ensure that your User Craft settings verifies new registrations. This ensures that not just anyone with a valid provider account can gain automatic access to your control panel.

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
When Social Login looks to see if there's a matching Craft user on whether to register a new one or not, it'll compare the provider email to the Craft user email. You can change this in your provider settings to match other fields and attributes.

For example, you might be mapping the provider ID to a custom field, and you want to match on that instead.

## User Groups
You can also select any default User Groups newly registered users should be included to. This is in addition to the Craft **Default User Group** user setting.
