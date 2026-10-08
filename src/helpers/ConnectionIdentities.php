<?php
namespace verbb\sociallogin\helpers;

use verbb\sociallogin\models\Connection;

use craft\db\Connection as DbConnection;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

use yii\db\Transaction;

use RuntimeException;

class ConnectionIdentities
{
    // Static Methods
    // =========================================================================

    public static function getGroups(DbConnection $db, bool $unresolvedOnly = false): array
    {
        $table = '{{%social_login_connections}}';

        if (!$db->tableExists($table) || ($unresolvedOnly && !$db->columnExists($table, 'identityKey'))) {
            return [];
        }

        $query = (new Query())
            ->select(['id', 'userId', 'providerHandle', 'identifier'])
            ->from($table)
            ->orderBy(['id' => SORT_ASC]);

        if ($unresolvedOnly) {
            $query->where(['identityKey' => null]);
        }

        $groups = [];

        // Group opaque identifiers by their exact bytes, independently of database collation.
        foreach ($query->all($db) as $row) {
            $groups[Connection::identityKey($row['providerHandle'], $row['identifier'])][] = $row;
        }

        return $groups;
    }

    public static function find(DbConnection $db, string $providerHandle, string $identifier): array
    {
        $key = Connection::identityKey($providerHandle, $identifier);
        $rows = (new Query())->select(['id', 'userId', 'providerHandle', 'identifier', 'identityKey'])->from('{{%social_login_connections}}')
            ->where(['or', ['identityKey' => $key], [
                'identityKey' => null,
                'providerHandle' => $providerHandle,
                'identifier' => $identifier,
            ]])
            ->orderBy(['id' => SORT_ASC])
            ->all($db);

        // NULL keys retain unresolved legacy links. Never mistake them for an unknown identity and register again.
        return array_values(array_filter($rows, fn(array $row) => Connection::identityKey($row['providerHandle'], $row['identifier']) === $key));
    }

    public static function isConflict(array $connections): bool
    {
        return count(array_unique(array_column($connections, 'userId'))) > 1;
    }

    public static function fingerprint(array $connections): string
    {
        return hash('sha256', Json::encode(array_map(fn(array $row) => [(int)$row['id'], (int)$row['userId']], $connections)));
    }

    public static function migrate(Migration $migration): bool
    {
        $db = $migration->db;
        $table = '{{%social_login_connections}}';

        if (!$db->tableExists($table)) {
            return true;
        }

        if (!$db->columnExists($table, 'identityKey')) {
            $migration->addColumn($table, 'identityKey', $migration->char(64)->after('identifier'));
        } else {
            $migration->alterColumn($table, 'identityKey', $migration->char(64)->null());
        }

        $db->transaction(function() use ($db, $migration, $table) {
            // Rebuild from surviving records, including partial/manual repairs. NULL reserves no owner;
            // the original links remain discoverable and cannot fall through to first-time matching.
            $migration->update($table, ['identityKey' => null], '', [], false);

            foreach (self::getGroups($db) as $key => $connections) {
                if (self::isConflict($connections)) {
                    continue;
                }

                $keep = array_shift($connections);

                foreach ($connections as $connection) {
                    self::_moveTokens($db, (int)$connection['id'], (int)$keep['id']);
                    $migration->delete($table, ['id' => $connection['id']]);
                }

                $migration->update($table, ['identityKey' => $key], ['id' => $keep['id']], [], false);
            }
        });

        if (!isset($db->getSchema()->findIndexes($table)['social_login_identity_key_unq'])) {
            $migration->createIndex('social_login_identity_key_unq', $table, ['identityKey'], true);
        }

        return true;
    }

    public static function resolve(DbConnection $db, string $key, int $userId, string $fingerprint): void
    {
        // Serialize decisions so a stale form or concurrent login cannot change the ownership being approved.
        $db->transaction(function() use ($db, $key, $userId, $fingerprint) {
            $connections = self::getGroups($db)[$key] ?? [];

            if (!self::isConflict($connections) || !hash_equals(self::fingerprint($connections), $fingerprint)) {
                throw new RuntimeException('These connections have changed. Review the current accounts before choosing an owner.');
            }

            $owned = array_values(array_filter($connections, fn(array $row) => (int)$row['userId'] === $userId));

            if (!$owned) {
                throw new RuntimeException('Choose one of the Craft users already connected to this provider account.');
            }

            $keep = $owned[0];

            foreach ($connections as $connection) {
                if ((int)$connection['id'] === (int)$keep['id']) {
                    continue;
                }

                if ((int)$connection['userId'] === $userId) {
                    self::_moveTokens($db, (int)$connection['id'], (int)$keep['id']);
                } elseif ($db->tableExists('{{%auth_oauth_tokens}}')) {
                    $db->createCommand()->delete('{{%auth_oauth_tokens}}', [
                        'ownerHandle' => 'social-login',
                        'reference' => (string)$connection['id'],
                    ])->execute();
                }

                $db->createCommand()->delete('{{%social_login_connections}}', ['id' => $connection['id']])->execute();
            }

            $db->createCommand()->update('{{%social_login_connections}}', ['identityKey' => $key], ['id' => $keep['id']])->execute();
        }, Transaction::SERIALIZABLE);
    }

    private static function _moveTokens(DbConnection $db, int $fromId, int $toId): void
    {
        if ($db->tableExists('{{%auth_oauth_tokens}}')) {
            $db->createCommand()->update('{{%auth_oauth_tokens}}', ['reference' => (string)$toId], [
                'ownerHandle' => 'social-login',
                'reference' => (string)$fromId,
            ], [], false)->execute();
        }
    }
}
