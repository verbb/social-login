# Events

Social Login provides events for extending its functionality. Modules and plugins can register event listeners, typically in their `init()` methods, to change Social Login’s behaviour.

## Provider Events

### The `registerProviderTypes` Event
The event that is triggered when Social Login builds the list of available provider classes. Append your class to `$event->types` to register it. [Custom Providers](custom-providers.md) shows a complete provider class.

```php
use craft\events\RegisterComponentTypesEvent;
use modules\sitemodule\Example;
use verbb\sociallogin\services\Providers;
use yii\base\Event;

Event::on(Providers::class, Providers::EVENT_REGISTER_PROVIDER_TYPES, function(RegisterComponentTypesEvent $event) {
    $event->types[] = Example::class;
});
```

## User Events

### The `beforeLogin` Event
The event that is triggered before a user is logged in.

Set `$event->isValid` to `false` to stop the login, and the person stays logged out. Social Login logs in whichever user is in `$event->user` after your listener runs.

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

The user is already logged in, so this event can’t stop the login.

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

`$event->user` is the new, unsaved user, populated from the provider profile and your field mapping. Change its properties to adjust what’s saved, or set `$event->isValid` to `false` to stop the registration, in which case no user is created and the login fails.

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

The user has been saved, activated where your settings allow, and assigned to the default user groups.

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

You can change `$event->connection` before it’s validated and saved. This event can’t cancel the save.

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

This event can’t cancel the deletion.

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
