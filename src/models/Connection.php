<?php
namespace verbb\sociallogin\models;

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\base\ProviderInterface;

use Craft;
use craft\base\Model;
use craft\elements\User;

use verbb\auth\Auth;
use verbb\auth\models\Token;

class Connection extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $userId = null;
    public ?string $providerHandle = null;
    public ?string $identifier = null;
    public ?string $identityKey = null;

    private ?User $_user = null;


    // Public Methods
    // =========================================================================

    public function __toString(): string
    {
        return (string)$this->identifier;
    }

    public static function identityKey(string $providerHandle, string $identifier): string
    {
        $value = strlen($providerHandle) . ':' . $providerHandle . strlen($identifier) . ':' . $identifier;

        return hash('sha256', $value);
    }

    public function getLoginProvider(): ?ProviderInterface
    {
        if ($this->providerHandle) {
            return SocialLogin::$plugin->getProviders()->getProviderByHandle($this->providerHandle);
        }

        return null;
    }

    public function getUser(): ?User
    {
        if ($this->_user === null && $this->userId) {
            $this->_user = Craft::$app->getUsers()->getUserById($this->userId);
        }

        return $this->_user;
    }

    public function getToken(): ?Token
    {
        if ($this->id) {
            return Auth::getInstance()->getTokens()->getTokenByOwnerReference('social-login', $this->id);
        }

        return null;
    }

    public function getIsConnected(): bool
    {
        return (bool)$this->getToken();
    }
}
