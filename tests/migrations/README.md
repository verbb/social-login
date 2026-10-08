# Provider Identity Regression Fixtures

These fixtures exercise the recovery workflow for [#60](https://github.com/verbb/social-login/issues/60). Use disposable databases named `social_login_issue60`; the scripts refuse other database names. They do not load a site's database configuration unless you explicitly run the login fixture with that application's path.

## Migrations and Ownership Resolution

Install the plugin's Composer dependencies, or point `VERBB_SOCIAL_LOGIN_TEST_VENDOR` at a compatible Craft 5 vendor directory. Create an empty disposable database, then run from the plugin directory:

```bash
VERBB_SOCIAL_LOGIN_TEST_VENDOR=/path/to/vendor \
VERBB_SOCIAL_LOGIN_TEST_DSN='mysql:host=127.0.0.1;port=3306;dbname=social_login_issue60' \
VERBB_SOCIAL_LOGIN_TEST_USER=test \
VERBB_SOCIAL_LOGIN_TEST_PASSWORD=test \
php tests/migrations/connection-identities-fixture.php
```

For PostgreSQL, use a `pgsql:` DSN and the appropriate port and credentials. The fixture uses real Craft database classes and randomly prefixed tables. It removes those tables in `finally`, including after a failed assertion.

Coverage includes non-blocking upgrades with multiple conflicts, exact identity comparison, partial and previously completed migrations, stale ownership decisions, token scoping and rollback, same-user deduplication, unique-index enforcement, retries and missing tables.

## Returning Login and Shared-Account Connection

Install Craft and Social Login in a disposable application using the same database name, and apply migrations. Set its bootstrap up to load the plugin checkout under test, then run:

```bash
VERBB_SOCIAL_LOGIN_TEST_APP=/path/to/disposable/craft \
php tests/migrations/identity-login-fixture.php
```

This fixture uses real Craft users, connection persistence and the Social Login user service. It supplies synthetic Microsoft Entra profiles and captures the user selected for login without creating a browser login session. It verifies that an email/UPN change retains the connected Craft user, ambiguous identities cannot log in or register another user, direct connection saves cannot claim an unresolved identity, and an explicit ownership decision restores login. Rejected connections preserve users, connections and tokens. Fixture users and records are rolled back, and the site's original Craft edition is restored after temporarily enabling Pro for multiple users. No live provider or OAuth callback is exercised.

## Administrator Ownership Review

Using the same installed disposable application, run:

```bash
VERBB_SOCIAL_LOGIN_TEST_APP=/path/to/disposable/craft \
php tests/migrations/ownership-controller-fixture.php
```

This checks administrator-only access, rejection of site requests and GET mutations, CSRF protection, malformed selections, and a successful ownership decision while project configuration changes are disabled. Fixture users and connections are rolled back. The controller fixture does not replace browser verification of the review template.
