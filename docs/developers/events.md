# Events
Social Login provides a collection of events for extending its functionality. Modules and plugins can register event listeners, typically in their `init()` methods, to modify Social Login’s behavior.

## User Events

### The `beforeLogin` Event
The event that is triggered before a user is logged in.

```php
use verbb\sociallogin\events\UserEvent;
use verbb\sociallogin\services\Users;
use yii\base\Event;

Event::on(Users::class, Users::EVENT_BEFORE_LOGIN, function(UserEvent $event) {
    $user = $event->user;
    $userProfile = $event->userProfile;
    $provider = $event->provider;
    // ...
});
```

### The `afterLogin` Event
The event that is triggered after a user is logged in.

```php
use verbb\sociallogin\events\UserEvent;
use verbb\sociallogin\services\Users;
use yii\base\Event;

Event::on(Users::class, Users::EVENT_AFTER_LOGIN, function(UserEvent $event) {
    $user = $event->user;
    $userProfile = $event->userProfile;
    $provider = $event->provider;
    // ...
});
```

### The `beforeRegister` Event
The event that is triggered before a user is registered.

```php
use verbb\sociallogin\events\UserEvent;
use verbb\sociallogin\services\Users;
use yii\base\Event;

Event::on(Users::class, Users::EVENT_BEFORE_REGISTER, function(UserEvent $event) {
    $user = $event->user;
    $userProfile = $event->userProfile;
    $provider = $event->provider;
    // ...
});
```

### The `afterRegister` Event
The event that is triggered after a user is registered.

```php
use verbb\sociallogin\events\UserEvent;
use verbb\sociallogin\services\Users;
use yii\base\Event;

Event::on(Users::class, Users::EVENT_AFTER_REGISTER, function(UserEvent $event) {
    $user = $event->user;
    $userProfile = $event->userProfile;
    $provider = $event->provider;
    // ...
});
```


## Connection Events

### The `beforeSaveConnection` Event
The event that is triggered before a connection is saved.

```php
use verbb\sociallogin\events\ConnectionEvent;
use verbb\sociallogin\services\Connections;
use yii\base\Event;

Event::on(Connections::class, Connections::EVENT_BEFORE_SAVE_CONNECTION, function(ConnectionEvent $event) {
    $connection = $event->connection;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveConnection` Event
The event that is triggered after a connection is saved.

```php
use verbb\sociallogin\events\ConnectionEvent;
use verbb\sociallogin\services\Connections;
use yii\base\Event;

Event::on(Connections::class, Connections::EVENT_AFTER_SAVE_CONNECTION, function(ConnectionEvent $event) {
    $connection = $event->connection;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeleteConnection` Event
The event that is triggered before a connection is deleted.

```php
use verbb\sociallogin\events\ConnectionEvent;
use verbb\sociallogin\services\Connections;
use yii\base\Event;

Event::on(Connections::class, Connections::EVENT_BEFORE_DELETE_CONNECTION, function(ConnectionEvent $event) {
    $connection = $event->connection;
    // ...
});
```

### The `afterDeleteConnection` Event
The event that is triggered after a connection is deleted.

```php
use verbb\sociallogin\events\ConnectionEvent;
use verbb\sociallogin\services\Connections;
use yii\base\Event;

Event::on(Connections::class, Connections::EVENT_AFTER_DELETE_CONNECTION, function(ConnectionEvent $event) {
    $connection = $event->connection;
    // ...
});
```
