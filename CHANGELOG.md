# Changelog

## 2.0.9 - 2025-05-01

### Fixed
- Fix an error when logging errors for unsupported email providers.
- Fix status indicator for various UI elements.
- Fix compatibility with Craft 5.2+ and the session-expired login modal.

## 2.0.8 - 2025-04-12

### Fixed
- Fix compatibility with Craft 5.2+ and the session-expired login modal.
- Fix an error for some providers (GitHub, GitLab, PayPal).

## 2.0.7 - 2024-09-14

### Added
- Add Microsoft Entra provider.

## 2.0.6 - 2024-09-07

### Added
- Add the ability to set the endpoint version for Azure provider.

## 2.0.5 - 2024-08-11

### Changed
- CP Login templates now are wrapped with a `.social-login-cp-container` div.
- Update `syncProfile` to be disabled by default.
- Update English translations.

### Fixed
- Fix custom CP login template not resolving correctly.
- Fix HubSpot default scopes.

## 2.0.4 - 2024-07-17

### Fixed
- Fix an error with Amazon Cognito provider.

## 2.0.3 - 2024-07-15

### Added
- Add `sendActivationEmail` plugin setting.
- Add the ability to set `data` for a login request, where users might want to store extra information.
- Add Amazon Cognito provider.

### Changed
- Revert callback URI change for `cpTrigger = null` (for detached CPs).

### Fixed
- Fix race conditions with plugin initialization. (thanks @nfourtythree).
- Fix activation email logic.

## 2.0.2 - 2024-05-29

### Added
- Added a new Plugin setting for syncing user profiles for existing accounts (thanks @SwiseWeb).
- Add the ability to set `data` for a login request, where users might want to store extra information.

### Changed
- Update English translations.

### Fixed
- Fix URL normalization for headless redirect URI.
- Fix an error with `headlessMode` and CP-based logins.
- Fix LinkedIn provider and the v2 API.
- Fix an error when using “Name” as a value for matching an existing user. This is now “Full Name”.
- Fix control panel login not working correctly.

## 2.0.1 - 2024-05-14

### Fixed
- Fix an error with Salesforce.

## 2.0.0 - 2024-05-13

### Added
- Add improved session-handling for authorization and callback methods, to improve failed sessions in some cases.
- Add IdentityServer4 provider.
- Add Salesforce provider.
- Add logging when a provider cannot be saved.

### Changed
- Now requires PHP `8.2.0+`.
- Now requires Craft `5.0.0+`.

### Fixed
- Fix an error when uninstalling the plugin.
- Fix Salesforce provider.
- Fix Apple provider not saving.

## 1.0.21 - 2025-05-01

### Fixed
- Fix an error when logging errors for unsupported email providers.

## 1.0.20 - 2024-09-14

### Added
- Add Microsoft Entra provider.

## 1.0.19 - 2024-09-07

### Added
- Add the ability to set the endpoint version for Azure provider.

## 1.0.18 - 2024-08-11

### Changed
- CP Login templates now are wrapped with a `.social-login-cp-container` div.
- Update `syncProfile` to be disabled by default.
- Update English translations.

### Fixed
- Fix custom CP login template not resolving correctly.
- Fix HubSpot default scopes.

## 1.0.17 - 2024-07-17

### Fixed
- Fix an error with Amazon Cognito provider.

## 1.0.16 - 2024-07-15

### Added
- Add `sendActivationEmail` plugin setting.
- Add the ability to set `data` for a login request, where users might want to store extra information.
- Add Amazon Cognito provider.

### Changed
- Now requires Craft 4.3.5+.
- Revert callback URI change for `cpTrigger = null` (for detached CPs).

### Fixed
- Fix race conditions with plugin initialization. (thanks @nfourtythree).
- Fix activation email logic.

## 1.0.15 - 2024-05-29

### Added
- Added a new Plugin setting for syncing user profiles for existing accounts (thanks @SwiseWeb).

### Changed
- Update English translations.

### Fixed
- Fix URL normalization for headless redirect URI.
- Fix an error with `headlessMode` and CP-based logins.
- Fix LinkedIn provider and the v2 API.

## 1.0.14 - 2024-04-29

### Added
- Add support for `headlessMode` redirect URIs.

### Changed
- Update English translations.

## 1.0.13 - 2024-04-05

### Added
- Add improved session-handling for authorization and callback methods, to improve failed sessions in some cases.

## 1.0.12 - 2024-04-04

### Fixed
- Fix an error when uninstalling the plugin.
- Fix Salesforce provider.

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
