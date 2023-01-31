<?php
namespace verbb\sociallogin\events;

use verbb\sociallogin\models\Connection;

use yii\base\Event;

class ConnectionEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Connection $connection = null;
    public bool $isNew = false;

}
