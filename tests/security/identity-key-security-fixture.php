<?php
$vendorDir = getenv('VERBB_SOCIAL_LOGIN_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';

require $vendorDir . '/autoload.php';
require __DIR__ . '/../../src/models/Connection.php';

use verbb\sociallogin\models\Connection;

function check(string $description, bool $condition): void
{
    if (!$condition) {
        throw new RuntimeException("Failed: $description");
    }

    echo "PASS: $description\n";
}

$key = Connection::identityKey('provider', 'Account-ID');
check('identity key is deterministic SHA-256 hex', strlen($key) === 64 && ctype_xdigit($key) && $key === Connection::identityKey('provider', 'Account-ID'));
check('provider identifiers remain case-sensitive', $key !== Connection::identityKey('provider', 'account-id'));
check('provider identifiers preserve surrounding whitespace', Connection::identityKey('provider', 'account-id') !== Connection::identityKey('provider', ' account-id'));
check('provider handles remain part of the identity', $key !== Connection::identityKey('other-provider', 'Account-ID'));
check('length prefixes prevent concatenation ambiguity', Connection::identityKey('ab', 'c') !== Connection::identityKey('a', 'bc'));
