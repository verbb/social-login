<?php

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\base\Provider;
use verbb\sociallogin\controllers\SettingsController;
use verbb\sociallogin\models\Settings;
use verbb\sociallogin\services\Providers;

$vendorPath = getenv('VERBB_SOCIAL_LOGIN_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';

if (!is_file($vendorPath . '/autoload.php')) {
    throw new RuntimeException('Set VERBB_SOCIAL_LOGIN_TEST_VENDOR to a Craft 5 vendor directory.');
}

require $vendorPath . '/autoload.php';
require $vendorPath . '/yiisoft/yii2/Yii.php';
require $vendorPath . '/craftcms/cms/src/Craft.php';
require dirname(__DIR__, 2) . '/src/base/ProviderInterface.php';
require dirname(__DIR__, 2) . '/src/base/Provider.php';
require dirname(__DIR__, 2) . '/src/base/PluginTrait.php';
require dirname(__DIR__, 2) . '/src/models/Settings.php';
require dirname(__DIR__, 2) . '/src/SocialLogin.php';
require dirname(__DIR__, 2) . '/src/services/Providers.php';
require dirname(__DIR__, 2) . '/src/controllers/SettingsController.php';

final class ProviderConfigPersistenceFixtureConfig
{
    public function __construct(public array $config)
    {
    }

    public function getConfigFromFile(string $filename): array
    {
        return $filename === 'social-login' ? $this->config : [];
    }
}

final class ProviderConfigPersistenceFixturePlugins
{
    public ?array $savedSettings = null;

    public function __construct(public array $pluginInfo, public SocialLogin $plugin)
    {
    }

    public function getStoredPluginInfo(string $handle): ?array
    {
        return $handle === 'social-login' ? $this->pluginInfo : null;
    }

    public function getPlugin(string $handle): ?SocialLogin
    {
        return $handle === 'social-login' ? $this->plugin : null;
    }

    public function savePluginSettings(SocialLogin $plugin, array $settings): bool
    {
        $this->savedSettings = $settings;

        return true;
    }
}

final class ProviderConfigPersistenceFixtureApp extends yii\base\Component
{
    public function __construct(
        public ProviderConfigPersistenceFixtureConfig $config,
        public ProviderConfigPersistenceFixturePlugins $plugins,
    ) {
        parent::__construct();
    }

    public function getConfig(): ProviderConfigPersistenceFixtureConfig
    {
        return $this->config;
    }

    public function getPlugins(): ProviderConfigPersistenceFixturePlugins
    {
        return $this->plugins;
    }
}

final class ProviderConfigPersistenceFixtureProvider extends Provider
{
    public static string $handle = 'fixture';

    public string $clientId = '';
    public string $clientSecret = '';
    public string $customSetting = '';
    public string $customEnvironment = '';
    public array $nestedSetting = [];

    private string $_validatedClientSecret = '';
    private array $_validatedNestedSetting = [];

    public static function displayName(): string
    {
        return 'Fixture';
    }

    public function settingsAttributes(): array
    {
        return array_merge(parent::settingsAttributes(), [
            'clientId',
            'clientSecret',
            'customSetting',
            'customEnvironment',
            'nestedSetting',
        ]);
    }

    public function beforeValidate(): bool
    {
        $this->_validatedClientSecret = $this->clientSecret;
        $this->_validatedNestedSetting = $this->nestedSetting;

        return parent::beforeValidate();
    }

    public function getValidatedClientSecret(): string
    {
        return $this->_validatedClientSecret;
    }

    public function getValidatedNestedSetting(): array
    {
        return $this->_validatedNestedSetting;
    }

    protected function defineRules(): array
    {
        return [];
    }
}

function providerConfigPersistenceFixtureAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function providerConfigPersistenceFixtureContainsMarker(array $settings): bool
{
    $encoded = json_encode($settings, JSON_THROW_ON_ERROR);

    return str_contains($encoded, 'config-marker') || str_contains($encoded, 'legacy-duplicate-marker');
}

$storedSettings = [
    'enableLogin' => false,
    'providers' => [
        'fixture' => [
            'clientId' => 'stored-client-id',
            'clientSecret' => 'legacy-duplicate-marker',
            'customSetting' => 'stored-custom-setting',
            'customEnvironment' => '$FIXTURE_ENV_REFERENCE',
            'nestedSetting' => [
                'locked' => 'legacy-duplicate-marker-nested',
                'kept' => 'stored-nested-value',
            ],
        ],
        'other' => [
            'clientSecret' => 'legacy-duplicate-marker-other',
            'customEnvironment' => '$OTHER_ENV_REFERENCE',
        ],
        'customProvider' => [
            'customValue' => 'custom-provider-value',
        ],
    ],
];

$config = [
    'providers' => [
        'fixture' => [
            'clientSecret' => 'resolved-config-marker',
            'nestedSetting' => [
                'locked' => 'resolved-config-marker-nested',
            ],
            'scopes' => ['config-marker-scope'],
        ],
        'other' => [
            'clientSecret' => 'resolved-config-marker-other',
        ],
    ],
];

$settingsModel = new Settings();
$plugin = (new ReflectionClass(SocialLogin::class))->newInstanceWithoutConstructor();
$settingsProperty = new ReflectionProperty(craft\base\Plugin::class, '_settings');
$settingsProperty->setValue($plugin, $settingsModel);
SocialLogin::$plugin = $plugin;

$plugins = new ProviderConfigPersistenceFixturePlugins(['settings' => $storedSettings], $plugin);
Craft::$app = new ProviderConfigPersistenceFixtureApp(
    new ProviderConfigPersistenceFixtureConfig($config),
    $plugins,
);

$runtimeProviders = $settingsModel->getProviderSettings();
providerConfigPersistenceFixtureAssert($runtimeProviders['fixture']['clientSecret'] === 'resolved-config-marker', 'Runtime provider settings must retain config overrides.');
providerConfigPersistenceFixtureAssert($runtimeProviders['fixture']['nestedSetting']['locked'] === 'resolved-config-marker-nested', 'Nested runtime config overrides must remain effective.');
providerConfigPersistenceFixtureAssert($runtimeProviders['fixture']['nestedSetting']['kept'] === 'stored-nested-value', 'Runtime merging must preserve stored nested siblings.');

$persistentProviders = $settingsModel->getProviderSettingsForPersistence();
providerConfigPersistenceFixtureAssert(!isset($persistentProviders['fixture']['clientSecret']), 'Config-owned scalar values must be excluded from persistence.');
providerConfigPersistenceFixtureAssert(!isset($persistentProviders['fixture']['nestedSetting']['locked']), 'Config-owned nested values must be excluded from persistence.');
providerConfigPersistenceFixtureAssert($persistentProviders['fixture']['nestedSetting']['kept'] === 'stored-nested-value', 'Non-overridden nested values must survive persistence filtering.');
providerConfigPersistenceFixtureAssert($persistentProviders['fixture']['customEnvironment'] === '$FIXTURE_ENV_REFERENCE', 'Environment references must survive persistence filtering.');
providerConfigPersistenceFixtureAssert($persistentProviders['customProvider']['customValue'] === 'custom-provider-value', 'Unknown custom provider settings must survive persistence filtering.');
providerConfigPersistenceFixtureAssert(!providerConfigPersistenceFixtureContainsMarker($persistentProviders), 'Persistence filtering must remove existing config-owned marker values across providers.');

$settingsController = (new ReflectionClass(SettingsController::class))->newInstanceWithoutConstructor();
$prepareSettings = new ReflectionMethod(SettingsController::class, 'prepareSubmittedSettings');
$preparedSettings = $prepareSettings->invoke($settingsController, ['enableLogin' => true]);
providerConfigPersistenceFixtureAssert(!providerConfigPersistenceFixtureContainsMarker($preparedSettings), 'General settings saves must not persist config-owned marker values.');
providerConfigPersistenceFixtureAssert($preparedSettings['providers']['customProvider']['customValue'] === 'custom-provider-value', 'General settings saves must preserve custom provider settings.');

$provider = new ProviderConfigPersistenceFixtureProvider($runtimeProviders['fixture']);
$providerService = new Providers();
$saved = $providerService->saveProvider($provider, [
    'clientId' => 'submitted-client-id',
    'clientSecret' => 'submitted-config-owned-value',
    'customSetting' => 'submitted-custom-setting',
    'customEnvironment' => '$SUBMITTED_ENV_REFERENCE',
    'nestedSetting' => [
        'locked' => 'submitted-config-owned-nested-value',
        'kept' => 'submitted-nested-value',
        'added' => 'submitted-added-value',
    ],
    'unknownSetting' => 'must-not-persist',
]);

providerConfigPersistenceFixtureAssert($saved, 'Provider save must succeed.');
providerConfigPersistenceFixtureAssert(is_array($plugins->savedSettings), 'Provider save must write plugin settings.');
providerConfigPersistenceFixtureAssert(!providerConfigPersistenceFixtureContainsMarker($plugins->savedSettings), 'Provider save must remove config-owned marker values across all providers.');
providerConfigPersistenceFixtureAssert($provider->getValidatedClientSecret() === 'resolved-config-marker', 'Validation must retain config-owned scalar values at runtime.');
providerConfigPersistenceFixtureAssert($provider->getValidatedNestedSetting()['locked'] === 'resolved-config-marker-nested', 'Validation must retain config-owned nested values at runtime.');
providerConfigPersistenceFixtureAssert($provider->getValidatedNestedSetting()['kept'] === 'submitted-nested-value', 'Validation must receive submitted non-overridden nested values.');

$savedProvider = $plugins->savedSettings['providers']['fixture'];
providerConfigPersistenceFixtureAssert($savedProvider['clientId'] === 'submitted-client-id', 'Ordinary submitted settings must persist.');
providerConfigPersistenceFixtureAssert(!isset($savedProvider['clientSecret']), 'Config-owned submitted scalar values must not persist.');
providerConfigPersistenceFixtureAssert(!isset($savedProvider['nestedSetting']['locked']), 'Config-owned submitted nested values must not persist.');
providerConfigPersistenceFixtureAssert($savedProvider['nestedSetting']['kept'] === 'submitted-nested-value', 'Submitted nested siblings must persist.');
providerConfigPersistenceFixtureAssert($savedProvider['nestedSetting']['added'] === 'submitted-added-value', 'Custom submitted nested settings must persist.');
providerConfigPersistenceFixtureAssert($savedProvider['customSetting'] === 'submitted-custom-setting', 'Custom provider settings must persist.');
providerConfigPersistenceFixtureAssert($savedProvider['customEnvironment'] === '$SUBMITTED_ENV_REFERENCE', 'Submitted environment references must persist unchanged.');
providerConfigPersistenceFixtureAssert(!isset($savedProvider['unknownSetting']), 'Unknown submitted settings must not persist.');
providerConfigPersistenceFixtureAssert($plugins->savedSettings['providers']['other']['customEnvironment'] === '$OTHER_ENV_REFERENCE', 'Unrelated provider environment references must survive.');
providerConfigPersistenceFixtureAssert($plugins->savedSettings['providers']['customProvider']['customValue'] === 'custom-provider-value', 'Unrelated custom provider settings must survive.');
providerConfigPersistenceFixtureAssert($plugins->savedSettings['enableLogin'] === false, 'Raw stored general settings must survive provider saves.');

providerConfigPersistenceFixtureAssert($providerService->saveProvider($provider, [
    'nestedSetting' => [
        'locked' => 'submitted-config-owned-nested-value',
    ],
]), 'Provider save with a fully config-owned nested submission must succeed.');
providerConfigPersistenceFixtureAssert($provider->getValidatedNestedSetting()['locked'] === 'resolved-config-marker-nested', 'Validation must restore a fully config-owned nested mapping.');
providerConfigPersistenceFixtureAssert($plugins->savedSettings['providers']['fixture']['nestedSetting'] === [], 'A fully config-owned nested mapping must persist as an empty safe value.');

$provider->customSetting = 'programmatic-custom-setting';
$provider->nestedSetting = [
    'locked' => 'resolved-config-marker-nested',
    'kept' => 'programmatic-nested-value',
];

providerConfigPersistenceFixtureAssert($providerService->saveProvider($provider), 'Programmatic provider save must remain supported.');
providerConfigPersistenceFixtureAssert(!providerConfigPersistenceFixtureContainsMarker($plugins->savedSettings), 'Programmatic provider save must not persist config-owned marker values.');
providerConfigPersistenceFixtureAssert($plugins->savedSettings['providers']['fixture']['customSetting'] === 'programmatic-custom-setting', 'Programmatic provider changes must persist.');
providerConfigPersistenceFixtureAssert($plugins->savedSettings['providers']['fixture']['nestedSetting']['kept'] === 'programmatic-nested-value', 'Programmatic nested provider changes must persist.');

echo "Provider config persistence security fixture passed.\n";
