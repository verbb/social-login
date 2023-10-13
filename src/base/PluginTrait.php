<?php
namespace verbb\sociallogin\base;

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\services\Connections;
use verbb\sociallogin\services\Providers;
use verbb\sociallogin\services\Service;
use verbb\sociallogin\services\Users;

use verbb\base\LogTrait;
use verbb\base\helpers\Plugin;

use verbb\auth\Auth;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static ?SocialLogin $plugin = null;


    // Traits
    // =========================================================================

    use LogTrait;
    

    // Static Methods
    // =========================================================================

    public static function config(): array
    {
        Auth::registerModule();
        Plugin::bootstrapPlugin('social-login');

        return [
            'components' => [
                'connections' => Connections::class,
                'providers' => Providers::class,
                'service' => Service::class,
                'users' => Users::class,
            ],
        ];
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

}