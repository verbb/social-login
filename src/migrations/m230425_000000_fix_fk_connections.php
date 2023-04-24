<?php
namespace verbb\sociallogin\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

class m230425_000000_fix_fk_connections extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        MigrationHelper::dropForeignKeyIfExists('{{%social_login_connections}}', 'id');

        return true;
    }

    public function safeDown(): bool
    {
        echo "m230425_000000_fix_fk_connections cannot be reverted.\n";
        return false;
    }
}