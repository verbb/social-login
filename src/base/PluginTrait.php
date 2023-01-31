<?php
namespace verbb\sociallogin\base;

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\services\Connections;
use verbb\sociallogin\services\Providers;
use verbb\sociallogin\services\Service;
use verbb\sociallogin\services\Users;

use Craft;

use yii\log\Logger;

use verbb\auth\Auth;
use verbb\base\BaseHelper;

trait PluginTrait
{
    // Static Properties
    // =========================================================================

    public static SocialLogin $plugin;


    // Public Methods
    // =========================================================================

    public static function log($message, $attributes = []): void
    {
        if ($attributes) {
            $message = Craft::t('social-login', $message, $attributes);
        }

        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'social-login');
    }

    public static function error($message, $attributes = []): void
    {
        if ($attributes) {
            $message = Craft::t('social-login', $message, $attributes);
        }

        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'social-login');
    }


    // Public Methods
    // =========================================================================

    public function getConnections(): Connections
    {
        return $this->get('connections');
    }

    public function getProviders(): Providers
    {
        return $this->get('providers');
    }

    public function getService(): Service
    {
        return $this->get('service');
    }

    public function getUsers(): Users
    {
        return $this->get('users');
    }


    // Private Methods
    // =========================================================================

    private function _registerComponents(): void
    {
        $this->setComponents([
            'connections' => Connections::class,
            'providers' => Providers::class,
            'service' => Service::class,
            'users' => Users::class,
        ]);

        Auth::registerModule();
        BaseHelper::registerModule();
    }

    private function _registerLogTarget(): void
    {
        BaseHelper::setFileLogging('social-login');
    }

}