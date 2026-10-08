# Account Linking Regression Fixtures

These fixtures cover both reports in [#52](https://github.com/verbb/social-login/issues/52). Install Craft and Social Login in a disposable application with a database named `social_login_issue52`. Both fixtures refuse other database names. Configure its bootstrap to load the plugin checkout under test, then run from the plugin directory:

```bash
VERBB_SOCIAL_LOGIN_TEST_APP=/path/to/disposable/craft \
php tests/login/account-linking-fixture.php

VERBB_SOCIAL_LOGIN_TEST_APP=/path/to/disposable/craft \
php tests/login/control-panel-fixture.php
```

The account-linking fixture exercises the actual user and connection services with synthetic Entra profiles. It verifies explicit connections with different emails, returning identity-based login, rejected initial matches without duplicate records, account status and case-insensitive email collisions, registration-event changes, verified matching, and new-user activation.

The control-panel fixture exercises Craft’s user sidebar event, own-account controls, disabled providers, locked project configuration, and callback notifications. The OAuth exchange is synthetic; no external provider is contacted. It also verifies that connecting through the callback changes the available control from Connect to Disconnect.

Both scripts roll back their fixture records and restore the original Craft edition after temporarily enabling Pro. Run them serially on an isolated application. Browser verification should additionally cover the actual account screen and its POST disconnect action. No live Microsoft authorization is exercised by these fixtures.
