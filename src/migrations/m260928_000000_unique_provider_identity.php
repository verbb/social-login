<?php
namespace verbb\sociallogin\migrations;

use verbb\sociallogin\models\Connection;

use craft\db\Migration;
use craft\db\Query;

use RuntimeException;

class m260928_000000_unique_provider_identity extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $table = '{{%social_login_connections}}';

        if (!$this->db->columnExists($table, 'identityKey')) {
            $this->addColumn($table, 'identityKey', $this->char(64)->after('identifier'));
        }

        $rows = (new Query())
            ->select(['id', 'userId', 'providerHandle', 'identifier'])
            ->from($table)
            ->orderBy(['id' => SORT_ASC])
            ->all($this->db);
        $groups = [];

        foreach ($rows as $row) {
            $identityKey = Connection::identityKey($row['providerHandle'], $row['identifier']);
            $this->update($table, ['identityKey' => $identityKey], ['id' => $row['id']]);
            $row['identityKey'] = $identityKey;
            $groups[$identityKey][] = $row;
        }

        foreach ($groups as $connections) {
            if (count($connections) < 2) {
                continue;
            }

            if (count(array_unique(array_column($connections, 'userId'))) > 1) {
                $ids = implode(', ', array_column($connections, 'id'));
                $identity = $connections[0]['providerHandle'] . ':' . $connections[0]['identifier'];

                throw new RuntimeException("Provider identity $identity is connected to multiple Craft users through connection IDs $ids. Resolve the ownership conflict before retrying this migration.");
            }

            $keepId = array_shift($connections)['id'];

            foreach ($connections as $connection) {
                if ($this->db->tableExists('{{%auth_oauth_tokens}}')) {
                    $this->update('{{%auth_oauth_tokens}}', ['reference' => (string)$keepId], [
                        'ownerHandle' => 'social-login',
                        'reference' => (string)$connection['id'],
                    ]);
                }

                $this->delete($table, ['id' => $connection['id']]);
            }
        }

        $this->alterColumn($table, 'identityKey', $this->char(64)->notNull());
        $indexes = $this->db->getSchema()->findIndexes($table);

        if (!isset($indexes['social_login_identity_key_unq'])) {
            $this->createIndex('social_login_identity_key_unq', $table, ['identityKey'], true);
        }

        return true;
    }

    public function safeDown(): bool
    {
        $table = '{{%social_login_connections}}';
        $indexes = $this->db->getSchema()->findIndexes($table);

        if (isset($indexes['social_login_identity_key_unq'])) {
            $this->dropIndex('social_login_identity_key_unq', $table);
        }

        if ($this->db->columnExists($table, 'identityKey')) {
            $this->dropColumn($table, 'identityKey');
        }

        return true;
    }
}
