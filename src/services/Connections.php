<?php
namespace verbb\sociallogin\services;

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\events\ConnectionEvent;
use verbb\sociallogin\models\Connection;
use verbb\sociallogin\records\Connection as ConnectionRecord;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;

use yii\base\Component;

use verbb\auth\Auth;
use verbb\auth\models\Token;

class Connections extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_CONNECTION = 'beforeSaveConnection';
    public const EVENT_AFTER_SAVE_CONNECTION = 'afterSaveConnection';
    public const EVENT_BEFORE_DELETE_CONNECTION = 'beforeDeleteConnection';
    public const EVENT_AFTER_DELETE_CONNECTION = 'afterDeleteConnection';


    // Properties
    // =========================================================================

    private ?MemoizableArray $_connections = null;


    // Public Methods
    // =========================================================================

    public function getAllConnections(): array
    {
        return $this->_connections()->all();
    }

    public function getConnectionById(int $id): ?Connection
    {
        return $this->_connections()->firstWhere('id', $id);
    }

    public function getAllConnectionsForUser(int $userId): array
    {
        return $this->_connections()->where('userId', $userId)->all();
    }

    public function getAllConnectionsForProvider(string $providerHandle): array
    {
        return $this->_connections()->where('providerHandle', $providerHandle)->all();
    }

    public function getAllConnectionsForUserAndProvider(int $userId, string $providerHandle): array
    {
        return ArrayHelper::whereMultiple($this->getAllConnections(), ['userId' => $userId, 'providerHandle' => $providerHandle]);
    }

    public function getConnectionByUserAndProvider(int $userId, string $providerHandle): ?Connection
    {
        return ArrayHelper::firstValue($this->getAllConnectionsForUserAndProvider($userId, $providerHandle));
    }

    public function upsertConnection(Connection $connection, Token $token): bool
    {
        if (!$connection->id) {
            $matchedConnection = ConnectionRecord::findOne([
                'userId' => $connection->userId,
                'providerHandle' => $connection->providerHandle,
                'identifier' => $connection->identifier,
            ]);

            if ($matchedConnection) {
                $connection->id = $matchedConnection->id;
            }
        }

        return $this->saveConnection($connection, $token);
    }

    public function saveConnection(Connection $connection, Token $token, bool $runValidation = true): bool
    {
        $isNewConnection = !$connection->id;

        // Fire a 'beforeSaveConnection' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_CONNECTION)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_CONNECTION, new ConnectionEvent([
                'connection' => $connection,
                'isNew' => $isNewConnection,
            ]));
        }

        if ($runValidation && !$connection->validate()) {
            SocialLogin::info('Connection not saved due to validation error.');
            return false;
        }

        $connectionRecord = $this->_getConnectionRecordById($connection->id);
        $connectionRecord->userId = $connection->userId;
        $connectionRecord->providerHandle = $connection->providerHandle;
        $connectionRecord->identifier = $connection->identifier;

        $connectionRecord->save(false);

        if (!$connection->id) {
            $connection->id = $connectionRecord->id;
        }

        // Fire an 'afterSaveConnection' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_CONNECTION)) {
            $this->trigger(self::EVENT_AFTER_SAVE_CONNECTION, new ConnectionEvent([
                'connection' => $connection,
                'isNew' => $isNewConnection,
            ]));
        }

        // We should also create or update the OAuth token. Use the connection as a reference.
        $token->reference = $connection->id;
        Auth::$plugin->getTokens()->upsertToken($token);

        return true;
    }

    public function deleteConnectionById(int $connectionId): bool
    {
        $connection = $this->getConnectionById($connectionId);

        if (!$connection) {
            return false;
        }

        return $this->deleteConnection($connection);
    }

    public function deleteConnectionByUserAndProvider(int $userId, string $providerHandle): bool
    {
        $errors = [];
        $connections = $this->getAllConnectionsForUserAndProvider($userId, $providerHandle);

        // Find and delete all connections - just in case some duplicates have snuck in
        foreach ($connections as $connection) {
            if (!$this->deleteConnectionById($connection->id)) {
                $errors[] = true;
            }
        }

        return !$errors;
    }

    public function deleteConnection(Connection $connection): bool
    {
        // Fire a 'beforeDeleteConnection' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_CONNECTION)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_CONNECTION, new ConnectionEvent([
                'connection' => $connection,
            ]));
        }

        Db::delete('{{%social_login_connections}}', ['id' => $connection->id]);

        // Also delete any tokens
        Auth::$plugin->getTokens()->deleteTokenByOwnerReference('social-login', $connection->id);

        // Fire an 'afterDeleteConnection' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_CONNECTION)) {
            $this->trigger(self::EVENT_AFTER_DELETE_CONNECTION, new ConnectionEvent([
                'connection' => $connection,
            ]));
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _connections(): MemoizableArray
    {
        if (!isset($this->_connections)) {
            $connections = [];

            foreach ($this->_createConnectionQuery()->all() as $result) {
                $connections[] = new Connection($result);
            }

            $this->_connections = new MemoizableArray($connections);
        }

        return $this->_connections;
    }

    private function _createConnectionQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'userId',
                'providerHandle',
                'identifier',
            ])
            ->from(['{{%social_login_connections}}']);
    }

    private function _getConnectionRecordById(?int $connectionId = null): ConnectionRecord
    {
        if ($connectionId !== null) {
            if ($connectionRecord = ConnectionRecord::findOne($connectionId)) {
                return $connectionRecord;
            }
        }

        return new ConnectionRecord();
    }

}
