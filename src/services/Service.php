<?php
namespace verbb\sociallogin\services;

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\assetbundles\SocialLoginAsset;

use Craft;
use craft\base\Component;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\services\Plugins;

use yii\base\Event;

use Throwable;

class Service extends Component
{
    // Public Methods
    // =========================================================================

    public function getLoginUrl(string $handle, array $options = []): ?string
    {
        $options['provider'] = $handle;

        if ($provider = SocialLogin::$plugin->getProviders()->getProviderByHandle($handle)) {
            if ($provider->enabled && $provider::supportsLogin()) {
                return UrlHelper::actionUrl('social-login/auth/login', $options);
            }
        }

        return null;
    }

    public function getConnectUrl(string $handle, array $options = []): ?string
    {
        $options['provider'] = $handle;

        if ($provider = SocialLogin::$plugin->getProviders()->getProviderByHandle($handle)) {
            if ($provider->enabled) {
                return UrlHelper::actionUrl('social-login/auth/connect', $options);
            }
        }

        return null;
    }

    public function getDisconnectUrl(string $handle, array $options = []): ?string
    {
        $options['provider'] = $handle;

        if ($provider = SocialLogin::$plugin->getProviders()->getProviderByHandle($handle)) {
            if ($provider->enabled) {
                return UrlHelper::actionUrl('social-login/auth/disconnect', $options);
            }
        }

        return null;
    }

    public function isConnected(string $handle): bool
    {
        if ($provider = SocialLogin::$plugin->getProviders()->getProviderByHandle($handle)) {
            return $provider->isConnected();
        }

        return false;
    }

    public function renderUserSidebar(): string
    {
        return Craft::$app->getView()->renderTemplate('social-login/_includes/sidebar-pane');
    }

    public function renderCpLogin(): void
    {
        // Wait until plugins are loaded to check
        Event::on(Plugins::class, Plugins::EVENT_AFTER_LOAD_PLUGINS, function(Event $event) {
            try {
                $settings = SocialLogin::$plugin->getSettings();
                $request = Craft::$app->getRequest();
                $view = Craft::$app->getView();

                if ($settings->enableCpLogin && $request->getIsCpRequest()) {
                    $template = $this->_getTemplate('social-login/_includes/cp-login', $settings->cpLoginTemplate);

                    if ($template) {
                        $html = $view->renderTemplate($template);

                        $view->registerAssetBundle(SocialLoginAsset::class);
                        $view->registerJs('new Craft.SocialLogin.CpLoginForm(' . Json::encode([
                            'html' => $html,
                        ]) . ');');
                    }
                }
            } catch (Throwable $e) {
                SocialLogin::error('Unable to render CP Login template: “{message}” {file}:{line}', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        });
    }


    // Private Methods
    // =========================================================================

    private function _getTemplate(string $defaultTemplate, string $template): string
    {
        $template = $template ?: $defaultTemplate;
        $view = Craft::$app->getView();

        if ($view->doesTemplateExist($template, $view::TEMPLATE_MODE_CP)) {
            return $template;
        }

        if ($view->doesTemplateExist($template, $view::TEMPLATE_MODE_SITE)) {
            return $template;
        }

        return '';
    }

}
