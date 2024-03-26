# Changelog

## 1.0.11 - 2024-03-26

### Added
- Add Salesforce provider.
- Add logging when a provider cannot be saved.

### Fixed
- Fix Apple provider not saving.

## 1.0.10 - 2024-03-22

### Added
- Add IdentityServer4 provider.

## 1.0.9 - 2023-12-08

### Added
- Control Panel registrations are now enabled.
- Add `forceActivate` plugin setting to override Craft’s user verification handling and login users immediately and automatically.
- Add support for “Remember Me” handling, as per your Craft config settings, and when providing a `rememberMe` param for login.
- Add full name as a user field. (thanks @kennethormandy).
- Add session errors for fatal errors when processing login callback.

### Fixed
- Fix an error when calling `Provider::getToken()` for unauthenticated users.
- Fix callback URI when Craft’s `cpTrigger` is null (for detached CPs).
- Fix an error when saving plugin settings, overwriting provider settings.
- Fix Google offline access type.

## 1.0.8 - 2023-10-25

### Fixed
- Fix an issue where providers were flagging an error when email values from were returning anything other than `email`.
- Fix some duplicated user profile field handles for some clients.

## 1.0.7 - 2023-10-05
> {warning} If you are using LinkedIn, your LinkedIn app will need to now include the **Sign In with LinkedIn using OpenID Connect** product.

### Added
- Add FreeAgent provider.
- Add Telegram provider.
- Add “Use Sandbox” setting for PayPal.

### Changed
- Change LinkedIn to use new OpenID Connect API.

### Fixed
- Fix Shopify provider not including a configurable “Shop” setting.

## 1.0.6 - 2023-07-12

### Fixed
- Fix scopes when merging array values.

## 1.0.5 - 2023-07-11

### Fixed
- Fix scopes when merging array values.

## 1.0.4 - 2023-05-27

### Added
- Add current site support to Redirect URI for multi-sites.
- Add support for Azure for custom tenants.

## 1.0.3 - 2023-04-25

### Fixed
- Fix an incorrect foreign key constraint with connections.

## 1.0.2 - 2023-04-12

### Fixed
- Fix an error when setting custom scopes for providers.

## 1.0.1 - 2023-04-07

### Added
- Add Neon CRM as a provider.
- Add “Custom Domain” setting to Auth0 provider.

### Fixed
- Fix Redirect URI not working correctly for multi-sites.

## 1.0.0 - 2023-02-01

### Added
- Initial release
