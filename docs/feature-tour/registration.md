# Registration
In addition to allowing your users to login to the _existing_ accounts with your website, what happens when the user doesn't exist? We can configure Social Login to create (register) the Craft User account if it doesn't exist. It'll then go through the regular [login](docs:feature-tour/login) process, instead creating the user account in Craft if it doesn't already exist.

This requires Craft Pro, which adds support for multiple users.

:::warning
Not all providers support authenticating a user to allow them to register on your site. This is due to provider API limitations or their T&C's for using their APIs. Instead, you can use them to [connect](docs:feature-tour/connecting) an existing Craft account.
:::

## Front-end Registration
You don't need to adjust anything to your front-end templates. When the user returns from the offsite provider, they'll be auto-registered and logged into their new account.

## Control Panel Registration
Registration via the control panel is not allowed, as this would be a major security risk. All it would take is for someone to discover your login page to your control panel, click to sign in with their own social media account, and they would be instantly granted access to your control panel. As such, this isn't a great idea by any means!

More to the point - even if users were able to be registered in this way, it would require you to give all new users control panel user permission, which is almost certainly not desired.

As such, control panel handling is limited to _just_ login.

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
