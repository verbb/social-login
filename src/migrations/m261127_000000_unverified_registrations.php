<?php
namespace verbb\sociallogin\migrations;

use craft\db\Migration;

class m261127_000000_unverified_registrations extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $table = '{{%social_login_connections}}';

        if ($this->db->tableExists($table) && !$this->db->columnExists($table, 'unverifiedRegistration')) {
            $this->addColumn($table, 'unverifiedRegistration', $this->boolean()->notNull()->defaultValue(false)->after('identityKey'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        $table = '{{%social_login_connections}}';

        if ($this->db->tableExists($table) && $this->db->columnExists($table, 'unverifiedRegistration')) {
            $this->dropColumn($table, 'unverifiedRegistration');
        }

        return true;
    }
}
