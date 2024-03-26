<?php
namespace verbb\sociallogin\services;

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\base\Provider;
use verbb\sociallogin\base\ProviderInterface;
use verbb\sociallogin\models\MissingProvider;
use verbb\sociallogin\providers as providerTypes;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\errors\MissingComponentException;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\ProjectConfig as ProjectConfigHelper;

class Providers extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_PROVIDER_TYPES = 'registerProviderTypes';


    // Properties
    // =========================================================================

    private ?MemoizableArray $_providers = null;


    // Public Methods
    // =========================================================================

    public function getAllProviderTypes(): array
    {
        $providerTypes = [
            providerTypes\Amazon::class,
            providerTypes\Apple::class,
            providerTypes\Auth0::class,
            providerTypes\Azure::class,
            providerTypes\Basecamp::class,
            providerTypes\Bitbucket::class,
            providerTypes\Box::class,
            providerTypes\Buddy::class,
            providerTypes\Deezer::class,
            providerTypes\DeviantArt::class,
            providerTypes\Discord::class,
            providerTypes\Dribbble::class,
            providerTypes\Dropbox::class,
            providerTypes\Envato::class,
            providerTypes\Etsy::class,
            providerTypes\Eventbrite::class,
            providerTypes\Facebook::class,
            providerTypes\Fitbit::class,
            providerTypes\Foursquare::class,
            providerTypes\FreeAgent::class,
            providerTypes\GitHub::class,
            providerTypes\GitLab::class,
            providerTypes\Google::class,
            providerTypes\Gumroad::class,
            providerTypes\Harvest::class,
            providerTypes\Heroku::class,
            providerTypes\HubSpot::class,
            providerTypes\IdentityServer4::class,
            providerTypes\Imgur::class,
            providerTypes\Instagram::class,
            providerTypes\Jira::class,
            providerTypes\Line::class,
            providerTypes\LinkedIn::class,
            providerTypes\Linode::class,
            providerTypes\Mailchimp::class,
            providerTypes\Mastodon::class,
            providerTypes\Meetup::class,
            providerTypes\Microsoft::class,
            providerTypes\Myob::class,
            providerTypes\NeonCrm::class,
            providerTypes\PayPal::class,
            providerTypes\Pinterest::class,
            providerTypes\Pipedrive::class,
            providerTypes\Reddit::class,
            providerTypes\Salesforce::class,
            providerTypes\Shopify::class,
            providerTypes\Slack::class,
            providerTypes\Snapchat::class,
            providerTypes\SoundCloud::class,
            providerTypes\Spotify::class,
            providerTypes\Square::class,
            providerTypes\StackExchange::class,
            providerTypes\Strava::class,
            providerTypes\Stripe::class,
            providerTypes\Telegram::class,
            providerTypes\TikTok::class,
            providerTypes\Trello::class,
            providerTypes\Tumblr::class,
            providerTypes\Twitch::class,
            providerTypes\Twitter::class,
            providerTypes\Unsplash::class,
            providerTypes\Vend::class,
            providerTypes\Vimeo::class,
            providerTypes\Vkontakte::class,
            providerTypes\WeChat::class,
            providerTypes\Yahoo::class,
            providerTypes\Zendesk::class,
            providerTypes\Zoho::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $providerTypes,
        ]);
        $this->trigger(self::EVENT_REGISTER_PROVIDER_TYPES, $event);

        return $event->types;
    }

    public function createProvider(mixed $config): Provider
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        try {
            $provider = ComponentHelper::createComponent($config, ProviderInterface::class);
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            $provider = new MissingProvider($config);
        }

        return $provider;
    }

    public function getAllProviders(): array
    {
        return $this->_providers()->all();
    }

    public function getAllEnabledProviders(): array
    {
        return $this->_providers()->where('enabled', true)->all();
    }

    public function getAllLoginProviders(): array
    {
        $providers = [];

        foreach ($this->getAllProviders() as $provider) {
            if ($provider::supportsLogin() && $provider->enabled && $provider->loginEnabled) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }

    public function getAllCpLoginProviders(): array
    {
        $providers = [];

        foreach ($this->getAllProviders() as $provider) {
            if ($provider::supportsLogin() && $provider->enabled && $provider->cpLoginEnabled) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }

    public function getProviderByHandle(string $handle): ?Provider
    {
        return $this->_providers()->firstWhere('handle', $handle, true);
    }

    public function createProviderConfig(Provider $provider): array
    {
        $settings = $provider->getSettings();

        return ProjectConfigHelper::packAssociativeArrays($settings);
    }

    public function saveProvider(Provider $provider): bool
    {
        if (!$provider->validate()) {
            return false;
        }

        // Get to provider configs (merged from config and settings)
        $providerSettings = SocialLogin::$plugin->getSettings()->getProviderSettings();
        $providerSettings[$provider->handle] = $this->createProviderConfig($provider);

        // Fetch the rest of plugin settings and replace with correctly-merged provider settings
        $settings = SocialLogin::$plugin->getSettings()->toArray();
        $settings['providers'] = $providerSettings;

        $plugin = Craft::$app->getPlugins()->getPlugin('social-login');

        return Craft::$app->getPlugins()->savePluginSettings($plugin, $settings);
    }


    // Private Methods
    // =========================================================================

    private function _providers(): MemoizableArray
    {
        // Get the provider config from plugin settings to populate provider classes
        $providerSettings = SocialLogin::$plugin->getSettings()->getProviderSettings();

        if (!isset($this->_providers)) {
            $providers = [];

            foreach ($this->getAllProviderTypes() as $type) {
                // Fetch settings from the plugin settings
                $config = $providerSettings[$type::$handle] ?? [];
                $config['type'] = $type;

                $providers[] = $this->createProvider($config);
            }

            $this->_providers = new MemoizableArray($providers);
        }

        return $this->_providers;
    }

}
