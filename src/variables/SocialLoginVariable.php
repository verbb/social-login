<?php
namespace verbb\sociallogin\variables;

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\base\ProviderInterface;

use verbb\auth\helpers\Session;

class SocialLoginVariable
{
    // Public Methods
    // =========================================================================

    public function getProviders(): array
    {
        return SocialLogin::$plugin->getProviders()->getAllProviders();
    }

    public function getEnabledProviders(): array
    {
        return SocialLogin::$plugin->getProviders()->getAllEnabledProviders();
    }

    public function getLoginProviders(): array
    {
        return SocialLogin::$plugin->getProviders()->getAllLoginProviders();
    }

    public function getCpLoginProviders(): array
    {
        return SocialLogin::$plugin->getProviders()->getAllCpLoginProviders();
    }

    public function getProvider(string $handle): ?ProviderInterface
    {
        return SocialLogin::$plugin->getProviders()->getProviderByHandle($handle);
    }

    public function getLoginUrl(string $handle, array $options = []): ?string
    {
        return SocialLogin::$plugin->getService()->getLoginUrl($handle, $options);
    }

    public function getConnectUrl(string $handle, array $options = []): ?string
    {
        return SocialLogin::$plugin->getService()->getConnectUrl($handle, $options);
    }

    public function getDisconnectUrl(string $handle, array $options = []): ?string
    {
        return SocialLogin::$plugin->getService()->getDisconnectUrl($handle, $options);
    }

    public function isConnected(string $handle): bool
    {
        return SocialLogin::$plugin->getService()->isConnected($handle);
    }

    public function getError(): mixed
    {
        return Session::getError('social-login');
    }

    public function getNotice(): mixed
    {
        return Session::getNotice('social-login');
    }

    public function getSuccess(): mixed
    {
        return Session::getSuccess('social-login');
    }

    public function getPlugin(): SocialLogin
    {
        return SocialLogin::$plugin;
    }
    
}