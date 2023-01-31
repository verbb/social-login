<?php
namespace verbb\sociallogin\records;

use craft\db\ActiveRecord;

class Connection extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%social_login_connections}}';
    }
}