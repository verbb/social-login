<?php
// Run only against a disposable database named social_login_issue60. No site bootstrap or credentials are loaded.
$vendorDir = getenv('VERBB_SOCIAL_LOGIN_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';
$loader = require $vendorDir . '/autoload.php';
require $vendorDir . '/yiisoft/yii2/Yii.php';
require $vendorDir . '/craftcms/cms/src/Craft.php';
$loader->addPsr4('verbb\\sociallogin\\', dirname(__DIR__, 2) . '/src', true);
spl_autoload_register(static function(string $class): void {
    $prefix = 'verbb\\sociallogin\\';
    if (str_starts_with($class, $prefix)) {
        require dirname(__DIR__, 2) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
}, true, true);

use craft\db\Connection;
use craft\db\Query;
use verbb\sociallogin\helpers\ConnectionIdentities;
use verbb\sociallogin\migrations\m260928_000000_unique_provider_identity;
use verbb\sociallogin\models\Connection as ConnectionModel;
use verbb\sociallogin\migrations\m261008_000000_preserve_provider_identity_conflicts;

$dsn = getenv('VERBB_SOCIAL_LOGIN_TEST_DSN');

if (!$dsn || !preg_match('/;dbname=social_login_issue60(?:;|$)/', $dsn)) {
    throw new RuntimeException('Set VERBB_SOCIAL_LOGIN_TEST_DSN to a disposable MySQL/PostgreSQL database named social_login_issue60.');
}

class ConflictTestApplication extends yii\console\Application
{
    public function getIsInstalled(): bool
    {
        return true;
    }

    public function getConfig(): object
    {
        return new class {
            public function getLoadingConfigFile(): ?string
            {
                return null;
            }

            public function getDb(): craft\config\DbConfig
            {
                return new craft\config\DbConfig(['charset' => 'utf8mb4']);
            }
        };
    }
}

$db = new Connection([
    'dsn' => $dsn,
    'username' => getenv('VERBB_SOCIAL_LOGIN_TEST_USER'),
    'password' => getenv('VERBB_SOCIAL_LOGIN_TEST_PASSWORD'),
    'tablePrefix' => 'issue60_' . bin2hex(random_bytes(4)) . '_',
    'commandClass' => craft\db\Command::class,
    'schemaMap' => [
        'mysql' => craft\db\mysql\Schema::class,
        'pgsql' => craft\db\pgsql\Schema::class,
    ],
]);
$app = new ConflictTestApplication([
    'id' => 'social-login-conflict-tests',
    'basePath' => __DIR__,
    'components' => ['db' => $db],
]);
$table = '{{%social_login_connections}}';
$tokens = '{{%auth_oauth_tokens}}';

function check(string $description, bool $condition): void
{
    if (!$condition) {
        throw new RuntimeException("Failed: $description");
    }

    echo "PASS: $description\n";
}

function rows(string $table): array
{
    return (new Query())->from($table)->orderBy('id')->all();
}

function reject(callable $callback, string $description): Throwable
{
    try {
        $callback();
    } catch (Throwable $e) {
        check($description, true);
        return $e;
    }

    throw new RuntimeException("Failed: $description (no exception)");
}

$legacy = new m260928_000000_unique_provider_identity(['db' => $db, 'compact' => true]);
$repair = new m261008_000000_preserve_provider_identity_conflicts(['db' => $db, 'compact' => true]);

function conflicts(): array
{
    global $db;
    return array_filter(ConnectionIdentities::getGroups($db, true), [ConnectionIdentities::class, 'isConflict']);
}

function resolveIdentity(string $identifier, int $userId, ?string $fingerprint = null): void
{
    global $db;
    $key = ConnectionModel::identityKey('microsoftEntra', $identifier);
    $group = ConnectionIdentities::getGroups($db)[$key] ?? [];
    ConnectionIdentities::resolve($db, $key, $userId, $fingerprint ?? ConnectionIdentities::fingerprint($group));
}

try {
    check('both migrations tolerate missing plugin tables', $legacy->safeUp() && $repair->safeUp());
    $db->createCommand()->createTable($table, [
        'id' => 'integer NOT NULL PRIMARY KEY', 'userId' => 'integer NOT NULL',
        'providerHandle' => 'varchar(64) NOT NULL', 'identifier' => 'varchar(255) NOT NULL',
    ])->execute();
    $db->createCommand()->createTable($tokens, [
        'id' => 'integer NOT NULL PRIMARY KEY', 'ownerHandle' => 'varchar(64) NOT NULL', 'reference' => 'varchar(255) NOT NULL',
    ])->execute();
    $db->createCommand()->batchInsert($table, ['id', 'userId', 'providerHandle', 'identifier'], [
        [1, 10, 'microsoftEntra', 'same-owner'], [2, 10, 'microsoftEntra', 'same-owner'],
        [3, 30, 'microsoftEntra', 'object-a'], [4, 40, 'microsoftEntra', 'object-a'],
        [5, 50, 'microsoftEntra', 'object-b'], [6, 60, 'microsoftEntra', 'object-b'],
        [7, 70, 'microsoftEntra', 'object-c'], [8, 80, 'microsoftEntra', 'object-c'],
        [9, 90, 'microsoftEntra', 'OBJECT-A'], [10, 100, 'microsoftEntra', 'object-a '],
        [11, 110, 'otherProvider', 'object-a'],
    ])->execute();
    $db->createCommand()->batchInsert($tokens, ['id', 'ownerHandle', 'reference'], [
        [1, 'social-login', '1'], [2, 'social-login', '2'], [3, 'social-login', '3'],
        [4, 'social-login', '4'], [5, 'another-plugin', '4'], [6, 'social-login', '4-other'],
    ])->execute();

    check('legacy upgrade completes despite three ownership conflicts', $legacy->safeUp());
    check('only same-user duplicate is removed', array_column(rows($table), 'id') == [1, 3, 4, 5, 6, 7, 8, 9, 10, 11]);
    check('conflicting connections are preserved with unclaimed keys', count(conflicts()) === 3 && (new Query())->from($table)->where(['identityKey' => null])->count() == 6);
    check('all tokens are preserved and same-user references consolidated', count(rows($tokens)) === 6 && rows($tokens)[1]['reference'] === '1');
    check('unresolved identities remain discoverable for login rejection', array_column(ConnectionIdentities::find($db, 'microsoftEntra', 'object-a'), 'userId') == [30, 40]);
    check('lookup preserves case', array_column(ConnectionIdentities::find($db, 'microsoftEntra', 'OBJECT-A'), 'id') == [9]);
    check('lookup preserves trailing whitespace', array_column(ConnectionIdentities::find($db, 'microsoftEntra', 'object-a '), 'id') == [10]);
    check('lookup preserves provider boundaries', array_column(ConnectionIdentities::find($db, 'otherProvider', 'object-a'), 'id') == [11]);
    check('unique index remains in place', isset($db->getSchema()->findIndexes($table)['social_login_identity_key_unq']));
    reject(fn() => $db->createCommand()->insert($table, [
        'id' => 90, 'userId' => 900, 'providerHandle' => 'microsoftEntra', 'identifier' => 'same-owner',
        'identityKey' => ConnectionModel::identityKey('microsoftEntra', 'same-owner'),
    ])->execute(), 'database still prevents duplicate claimed identities');

    $before = rows($table);
    $beforeTokens = rows($tokens);
    reject(fn() => resolveIdentity('object-a', 999), 'unrelated user cannot become the owner');
    reject(fn() => resolveIdentity('object-a', 30, str_repeat('f', 64)), 'stale review form cannot resolve a changed group');
    check('invalid decisions preserve connections and tokens', rows($table) === $before && rows($tokens) === $beforeTokens);

    // A competing claim makes the final UPDATE fail after cleanup, verifying transaction rollback.
    $db->createCommand()->insert($table, [
        'id' => 99, 'userId' => 999, 'providerHandle' => 'otherProvider', 'identifier' => 'competing-claim',
        'identityKey' => ConnectionModel::identityKey('microsoftEntra', 'object-a'),
    ])->execute();
    $withClaim = rows($table);
    reject(fn() => resolveIdentity('object-a', 30), 'failed ownership claim aborts resolution');
    check('failed resolution rolls back deleted connections and tokens', rows($table) === $withClaim && rows($tokens) === $beforeTokens);
    $db->createCommand()->delete($table, ['id' => 99])->execute();

    resolveIdentity('object-a', 30);
    check('chosen owner keeps original connection and claims unique key', array_column(ConnectionIdentities::find($db, 'microsoftEntra', 'object-a'), 'id') == [3] && ConnectionIdentities::find($db, 'microsoftEntra', 'object-a')[0]['identityKey'] !== null);
    check('only discarded owners Social Login tokens are deleted', array_column(rows($tokens), 'id') == [1, 2, 3, 5, 6]);
    reject(fn() => resolveIdentity('object-a', 40), 'replayed decision cannot move a resolved identity');
    check('unrelated conflicts remain intact', count(conflicts()) === 2);

    // The follow-up migration must preserve this operator decision and all remaining ambiguous links.
    $before = rows($table);
    $beforeTokens = rows($tokens);
    check('follow-up migration and retries complete', $repair->safeUp() && $repair->safeUp());
    check('follow-up migration is idempotent and preserves ownership decisions', rows($table) === $before && rows($tokens) === $beforeTokens);

    resolveIdentity('object-b', 50);
    $db->createCommand()->dropTable($tokens)->execute();
    resolveIdentity('object-c', 70);
    check('resolution supports legacy sites without Auth token tables', conflicts() === []);

    // Model a site that completed the published NOT NULL migration after manual cleanup.
    $db->createCommand()->alterColumn($table, 'identityKey', 'char(64) NOT NULL')->execute();
    $before = rows($table);
    check('already-upgraded schema receives the follow-up migration', $repair->safeUp());
    check('manual cleanup is respected; deleted records are not invented', rows($table) === $before && !(new Query())->from($table)->where(['id' => [4, 6, 8]])->exists());
    check('follow-up makes the key nullable', $db->getTableSchema($table, true)->columns['identityKey']->allowNull);

    // A failed old upgrade can leave a partial column, or manual work may have dropped its index.
    $db->createCommand()->dropIndex('social_login_identity_key_unq', $table)->execute();
    $db->createCommand()->update($table, ['identityKey' => null])->execute();
    $db->createCommand()->insert($table, ['id' => 12, 'userId' => 120, 'providerHandle' => 'microsoftEntra', 'identifier' => 'object-b'])->execute();
    check('partial schema and missing index recover automatically', $repair->safeUp() && count(conflicts()) === 1 && isset($db->getSchema()->findIndexes($table)['social_login_identity_key_unq']));
    check('partial recovery preserves all conflicting candidates', count(ConnectionIdentities::find($db, 'microsoftEntra', 'object-b')) === 2);

    $db->createCommand()->delete($table)->execute();
    check('empty table migrates successfully', $legacy->safeUp() && $repair->safeUp());
} finally {
    foreach ([$tokens, $table] as $fixtureTable) {
        if ($db->tableExists($fixtureTable, true)) {
            $db->createCommand()->dropTable($fixtureTable)->execute();
        }
    }
    $db->close();
}
