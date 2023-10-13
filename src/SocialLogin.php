<?php
namespace verbb\sociallogin;

use verbb\sociallogin\base\PluginTrait;
use verbb\sociallogin\models\Settings;
use verbb\sociallogin\variables\SocialLoginVariable;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;

class SocialLogin extends Plugin
{
    // Properties
    // =========================================================================

    public bool $hasCpSettings = true;
    public string $schemaVersion = '1.0.1';


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_registerVariables();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();

            Craft::$app->getView()->hook('cp.users.edit.details', [$this->getService(), 'renderUserSidebar']);
        }

        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            $this->_registerSiteRoutes();
        }

        // Defer most setup tasks until Craft is fully initialized:
        Craft::$app->onInit(function() {
            // Check to register the plugin for CP login
            $this->getService()->renderCpLogin();
        });
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('social-login/settings'));
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['social-login'] = 'social-login/settings';
            $event->rules['social-login/settings'] = 'social-login/settings';
            $event->rules['social-login/settings/general'] = 'social-login/settings';
            $event->rules['social-login/settings/providers'] = 'social-login/providers';
            $event->rules['social-login/settings/providers/edit/<handle:{handle}>'] = 'social-login/providers/edit';
        });
    }

    private function _registerSiteRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['social-login/auth/callback'] = 'social-login/auth/callback';
        });
    }

    private function _registerVariables(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('socialLogin', SocialLoginVariable::class);
        });
    }
}
