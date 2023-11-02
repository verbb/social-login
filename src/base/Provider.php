<?php
namespace verbb\sociallogin\base;

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\models\UserField;

use Craft;
use craft\base\SavableComponent;
use craft\elements\User;
use craft\fields;
use craft\helpers\StringHelper;

use Exception;

use verbb\auth\helpers\Provider as ProviderHelper;

abstract class Provider extends SavableComponent implements ProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsLogin(): bool
    {
        return true;
    }

    public static function log(Provider $provider, string $message, bool $throwError = false): void
    {
        SocialLogin::log($provider->name . ': ' . $message);

        if ($throwError) {
            throw new Exception($message);
        }
    }

    public static function error(Provider $provider, string $message, bool $throwError = false): void
    {
        SocialLogin::error($provider->name . ': ' . $message);

        if ($throwError) {
            throw new Exception($message);
        }
    }


    // Properties
    // =========================================================================

    public bool $enabled = false;
    public bool $loginEnabled = true;
    public bool $cpLoginEnabled = false;
    public string $matchUserSource = 'email';
    public string $matchUserDestination = 'email';
    public array $fieldMapping = [];

    // Set via config files
    public array $authorizationOptions = [];
    public array $scopes = [];
    public array $customProfileFields = [];


    // Public Methods
    // =========================================================================

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'enabled';
        $attributes[] = 'matchUserSource';
        $attributes[] = 'matchUserDestination';
        $attributes[] = 'loginEnabled';
        $attributes[] = 'cpLoginEnabled';
        $attributes[] = 'fieldMapping';

        return $attributes;
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $settings = SocialLogin::$plugin->getSettings();
        $enableRegistration = $settings->enableRegistration;
        $populateProfile = $settings->populateProfile;

        $rules[] = [
            ['matchUserSource', 'matchUserDestination'], 'required', 'when' => function($model) use ($enableRegistration) {
                return $model->enabled && $enableRegistration;
            },
        ];

        $rules[] = [
            ['fieldMapping'], 'validateFieldMapping', 'when' => function($model) use ($populateProfile) {
                return $model->enabled && $populateProfile;
            },
        ];

        return $rules;
    }

    public function getName(): string
    {
        return static::displayName();
    }

    public function getHandle(): string
    {
        return static::$handle;
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function isConnected(): bool
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser) {
            if ($connection = SocialLogin::$plugin->getConnections()->getConnectionByUserAndProvider($currentUser->id, $this->handle)) {
                return $connection->getIsConnected();
            }
        }

        return false;
    }

    public function getSettingsHtml(): ?string
    {
        $handle = StringHelper::toKebabCase(static::$handle);

        return Craft::$app->getView()->renderTemplate('social-login/providers/' . $handle, [
            'provider' => $this,
        ]);
    }

    public function getPrimaryColor(): ?string
    {
        return ProviderHelper::getPrimaryColor(static::$handle);
    }

    public function getIcon(): ?string
    {
        return ProviderHelper::getIcon(static::$handle);
    }

    public function getUserProfileOptions(): array
    {
        $options = [
            [
                'label' => 'ID',
                'value' => 'id',
            ],
            [
                'label' => 'Email',
                'value' => 'email',
            ],
        ];

        // Get available profile fields from providers and config
        $fields = array_merge($this->getUserProfileFields(), $this->customProfileFields);

        // Sort alphabetically
        uasort($fields, function($a, $b) {
            return strcmp(basename($a), basename($b));
        });

        foreach ($fields as $field) {
            $options[] = [
                'label' => self::titleizeForHumans($field),
                'value' => $field,
            ];
        }

        $options[] = [
            'label' => 'Response',
            'value' => 'response',
        ];

        return $options;
    }

    public function getUserProfileFields(): array
    {
        return [];
    }

    public function getCraftUserFields(): array
    {
        $fields = [
            new UserField([
                'name' => 'Username',
                'handle' => 'username',
            ]),
            new UserField([
                'name' => 'Email',
                'handle' => 'email',
                'required' => true,
            ]),
            new UserField([
                'name' => 'Name',
                'handle' => 'name',
            ]),
            new UserField([
                'name' => 'First Name',
                'handle' => 'firstName',
            ]),
            new UserField([
                'name' => 'Last Name',
                'handle' => 'lastName',
            ]),
            new UserField([
                'name' => 'Full Name',
                'handle' => 'fullName',
            ]),
            new UserField([
                'name' => 'Photo',
                'handle' => 'photo',
                'type' => UserField::TYPE_FILE_UPLOAD,
            ]),
        ];

        $fieldLayout = Craft::$app->getFields()->getLayoutByType(User::class);

        foreach ($fieldLayout->getCustomFields() as $field) {
            $fields[] = new UserField([
                'name' => $field->name,
                'handle' => 'field:' . $field->handle,
                'type' => $this->getFieldTypeForField(get_class($field)),
                'required' => (bool)$field->required,
            ]);
        }

        return $fields;
    }

    public function validateFieldMapping($attribute): void
    {
        foreach ($this->getCraftUserFields() as $field) {
            $value = $this->$attribute[$field->handle] ?? '';

            if ($field->required && $value === '') {
                $this->addError($attribute . '.' . $field->handle, Craft::t('social-login', '{name} must be mapped.', ['name' => $field->name]));
            }
        }
    }


    // Private Methods
    // =========================================================================

    private static function titleizeForHumans(string $value): string
    {
        $words = str_replace(['-', '_'], ' ', $value);

        return StringHelper::titleizeForHumans(implode(' ', StringHelper::toWords($words)));
    }

    private function getFieldTypeForField($fieldClass)
    {
        // Provide a map of all native Craft fields to the data we expect
        $fieldTypeMap = [
            fields\Assets::class => UserField::TYPE_ARRAY,
            fields\Categories::class => UserField::TYPE_ARRAY,
            fields\Checkboxes::class => UserField::TYPE_ARRAY,
            fields\Date::class => UserField::TYPE_DATECLASS,
            fields\Entries::class => UserField::TYPE_ARRAY,
            fields\Lightswitch::class => UserField::TYPE_BOOLEAN,
            fields\MultiSelect::class => UserField::TYPE_ARRAY,
            fields\Number::class => UserField::TYPE_FLOAT,
            fields\Table::class => UserField::TYPE_ARRAY,
            fields\Tags::class => UserField::TYPE_ARRAY,
            fields\Users::class => UserField::TYPE_ARRAY,
        ];

        return $fieldTypeMap[$fieldClass] ?? UserField::TYPE_STRING;
    }
}
