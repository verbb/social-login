<?php
namespace verbb\sociallogin\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

use verbb\auth\Auth;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Ensure that the Auth module kicks off setting up tables
        Auth::$plugin->migrator->up();

        $this->createTables();
        $this->addForeignKeys();

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropForeignKeys();
        $this->removeTables();

        // Delete all tokens for this plugin
        Auth::$plugin->getTokens()->deleteTokensByOwner('social-login');

        return true;
    }

    public function createTables(): void
    {
        $this->archiveTableIfExists('{{%social_login_connections}}');
        $this->createTable('{{%social_login_connections}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'providerHandle' => $this->string(64)->notNull(),
            'identifier' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    public function addForeignKeys(): void
    {
        $this->addForeignKey(null, '{{%social_login_connections}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%social_login_connections}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
    }

    public function removeTables(): void
    {
        $this->dropTable('{{%social_login_connections}}');
    }

    public function dropForeignKeys(): void
    {
        MigrationHelper::dropAllForeignKeysOnTable('{{%social_login_connections}}', $this);
    }

}
