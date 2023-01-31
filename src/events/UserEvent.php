<?php
namespace verbb\sociallogin\events;

use verbb\sociallogin\base\Provider;

use craft\elements\User;
use craft\events\CancelableEvent;

use verbb\auth\models\UserProfile;

class UserEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?User $user = null;
    public ?UserProfile $userProfile = null;
    public ?Provider $provider = null;
}
