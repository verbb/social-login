<?php
namespace verbb\sociallogin\services;

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\events\ConnectionEvent;
use verbb\sociallogin\helpers\ConnectionIdentities;
use verbb\sociallogin\models\Connection;
use verbb\sociallogin\records\Connection as ConnectionRecord;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;

use yii\base\Component;
use yii\db\Transaction;

use RuntimeException;
use Throwable;

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

    public function getAllConnectionsByProviderIdentifier(string $providerHandle, string $identifier): array
    {
        $rows = ConnectionIdentities::find(Craft::$app->getDb(), $providerHandle, $identifier);

        return array_map(fn(array $row) => new Connection($row), $rows);
    }

    public function getOwnershipConflicts(): array
    {
        return array_filter(ConnectionIdentities::getGroups(Craft::$app->getDb(), true), [ConnectionIdentities::class, 'isConflict']);
    }

    public function resolveOwnershipConflict(string $key, int $userId, string $fingerprint): void
    {
        ConnectionIdentities::resolve(Craft::$app->getDb(), $key, $userId, $fingerprint);
        $this->_connections = null;
    }

    public function upsertConnection(Connection $connection, Token $token): bool
    {
        if (!$connection->id) {
            $matchedConnections = $this->getAllConnectionsByProviderIdentifier($connection->providerHandle, $connection->identifier);

            foreach ($matchedConnections as $matchedConnection) {
                if ($matchedConnection->userId !== $connection->userId) {
                    SocialLogin::error('Provider identity “{provider}:{identifier}” is already connected to another user.', [
                        'provider' => $connection->providerHandle,
                        'identifier' => $connection->identifier,
                    ]);

                    return false;
                }
            }

            $connection->id = $matchedConnections[0]->id ?? null;
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

        try {
            $saved = Craft::$app->getDb()->transaction(function() use ($connection, $token, $isNewConnection) {
                // Check preserved legacy links as well as claimed keys, including calls that bypass upsertConnection().
                foreach ($this->getAllConnectionsByProviderIdentifier($connection->providerHandle, $connection->identifier) as $existing) {
                    if ($existing->userId !== $connection->userId) {
                        throw new RuntimeException('This provider account is connected to another Craft user.');
                    }
                }

                $connectionRecord = $this->_getConnectionRecordById($connection->id);
                $connectionRecord->userId = $connection->userId;
                $connectionRecord->providerHandle = $connection->providerHandle;
                $connectionRecord->identifier = $connection->identifier;
                $connectionRecord->identityKey = Connection::identityKey($connection->providerHandle, $connection->identifier);

                if (!$connectionRecord->save(false)) {
                    throw new RuntimeException('Connection record could not be saved.');
                }

                if (!$connection->id) {
                    $connection->id = $connectionRecord->id;
                }

                $token->reference = $connection->id;

                if (!Auth::getInstance()->getTokens()->upsertToken($token)) {
                    throw new RuntimeException('OAuth token could not be saved.');
                }

                if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_CONNECTION)) {
                    $this->trigger(self::EVENT_AFTER_SAVE_CONNECTION, new ConnectionEvent([
                        'connection' => $connection,
                        'isNew' => $isNewConnection,
                    ]));
                }

                return true;
            }, Transaction::SERIALIZABLE);
        } catch (Throwable $e) {
            SocialLogin::error('Unable to save login connection: {message}', ['message' => $e->getMessage()]);

            return false;
        }

        if ($saved) {
            $this->_connections = null;
        }

        return $saved;
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
        Auth::getInstance()->getTokens()->deleteTokenByOwnerReference('social-login', $connection->id);

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
                'identityKey',
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
