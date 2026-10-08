<?php
namespace verbb\sociallogin\migrations;

use verbb\sociallogin\helpers\ConnectionIdentities;

use craft\db\Migration;

class m260928_000000_unique_provider_identity extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Older installations must reach the same recoverable state without blocking their update.
        return ConnectionIdentities::migrate($this);
    }

    public function safeDown(): bool
    {
        $table = '{{%social_login_connections}}';

        if (!$this->db->tableExists($table)) {
            return true;
        }

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
