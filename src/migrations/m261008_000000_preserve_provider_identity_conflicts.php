<?php
namespace verbb\sociallogin\migrations;

use verbb\sociallogin\helpers\ConnectionIdentities;

use craft\db\Migration;

class m261008_000000_preserve_provider_identity_conflicts extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Also repairs sites that already applied the original migration or manually resolved their links.
        return ConnectionIdentities::migrate($this);
    }

    public function safeDown(): bool
    {
        // Unresolved links cannot be represented by the previous NOT NULL unique constraint.
        return false;
    }
}
