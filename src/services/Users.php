<?php
namespace verbb\sociallogin\services;

use verbb\sociallogin\SocialLogin;
use verbb\sociallogin\base\Provider;
use verbb\sociallogin\events\UserEvent;
use verbb\sociallogin\helpers\AssetHelper;
use verbb\sociallogin\models\Connection;
use verbb\sociallogin\models\UserField;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

use Throwable;

use verbb\auth\helpers\Session;
use verbb\auth\models\Token;
use verbb\auth\models\UserProfile;

class Users extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_LOGIN = 'beforeLogin';
    public const EVENT_AFTER_LOGIN = 'afterLogin';
    public const EVENT_BEFORE_REGISTER = 'beforeRegister';
    public const EVENT_AFTER_REGISTER = 'afterRegister';


    // Public Methods
    // =========================================================================

    public function loginOrRegisterUser(Provider $provider, Token $token): bool
    {
        // Get the remote user profile
        $userProfile = $provider->getUserProfile($token);

        // With the user authenticated, login or register
        $user = Craft::$app->getUser()->getIdentity();

        // Fetch plugin settings
        $settings = SocialLogin::$plugin->getSettings();

        if (!$user) {
            $user = $this->_getOrCreateUser($provider, $userProfile);

            if (!$user) {
                SocialLogin::error('Unable to register new user.');

                return false;
            }
        } else if ($settings->populateProfile && $settings->syncProfile) {
            // Ensure we sync the User's profile on each login/register request. This ensures profile data is synced
            //  when using an edit profile URL from the SSO provider, for example.

            $user = $this->_syncUserProfile($provider, $user, $userProfile);
            Craft::$app->getElements()->saveElement($user);
        }

        // Are we resuming an already-logged in session (through the modal login)? Ensure that things match, 
        // otherwise we risk auto-logging into another account that doesn't match the email.
        if ($loginName = Session::get('loginName')) {
            $resumingUser = Craft::$app->getUsers()->getUserByUsernameOrEmail($loginName);

            if ($resumingUser !== $user->email) {
                SocialLogin::error('Tried to resume session for “{email1}”, but did not match “{email2}”.', [
                    'email1' => $resumingUser,
                    'email2' => $user->email,
                ]);

                return false;
            }
        }

        // Create or update a connection for this user
        $connection = new Connection();
        $connection->userId = $user->id;
        $connection->providerHandle = $provider->handle;
        $connection->identifier = $userProfile->id;

        if (!SocialLogin::$plugin->getConnections()->upsertConnection($connection, $token)) {
            SocialLogin::error('Unable to save login connection.');

            return false;
        }

        // Trigger a `beforeLogin` event
        $event = new UserEvent([
            'user' => $user,
            'userProfile' => $userProfile,
            'provider' => $provider,
        ]);

        $this->trigger(self::EVENT_BEFORE_LOGIN, $event);

        if (!$event->isValid) {
            SocialLogin::error('User login cancelled by event.');

            return false;
        }

        $user = $event->user;

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $rememberMe = (bool)Session::get('rememberMe');

        if ($rememberMe && $generalConfig->rememberedUserSessionDuration !== 0) {
            $duration = $generalConfig->rememberedUserSessionDuration;
        } else {
            $duration = $generalConfig->userSessionDuration;
        }

        if (!Craft::$app->getUser()->login($user, $duration)) {
            Session::setError('social-login', Craft::t('social-login', 'Unable to login.'));

            return false;
        }

        // Trigger an `afterLogin` event
        $this->trigger(self::EVENT_AFTER_LOGIN, new UserEvent([
            'user' => $user,
            'userProfile' => $userProfile,
            'provider' => $provider,
        ]));

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _getOrCreateUser(Provider $provider, UserProfile $userProfile): User|bool
    {
        $settings = SocialLogin::$plugin->getSettings();

        // Find an existing Craft user with the same email
        $user = $this->_matchExistingUser($provider, $userProfile);

        if ($user) {
            // Check if the User profile should be remapped
            if ($settings->populateProfile && $settings->syncProfile) {
                $user = $this->_syncUserProfile($provider, $user, $userProfile);
                Craft::$app->getElements()->saveElement($user);
            }

            return $user;
        }

        // Check if we're allowing user registration at all
        if (!$settings->enableRegistration) {
            return false;
        }

        Craft::$app->requireEdition(Craft::Pro);

        // This point, we need to create a new user
        $user = $this->_createUser($provider, $userProfile);

        // Trigger a `beforeRegister` event
        $event = new UserEvent([
            'user' => $user,
            'userProfile' => $userProfile,
            'provider' => $provider,
        ]);

        $this->trigger(self::EVENT_BEFORE_REGISTER, $event);

        if (!$event->isValid) {
            SocialLogin::error('User registration cancelled by event.');

            return false;
        }

        $user = $event->user;

        // Some providers (Instagram) don't support emails, which is the bare-minimum requirement.
        if (!$user->email) {
            SocialLogin::error('Provider “{provider}” does not support emails, unable to create user.', ['provider' => $provider->handle]);
            SocialLogin::error($userProfile->response);

            return false;
        }

        if (!Craft::$app->getElements()->saveElement($user)) {
            $error = Craft::t('social-login', 'Unable to register user: {json}.', ['json' => Json::encode($user->getErrors())]);

            Session::setError('social-login', $error);
            SocialLogin::error($error);

            return false;
        }

        // Force-activation, regardless of site settings, so we can login immediately
        if ($settings->forceActivate) {
            Craft::$app->getUsers()->activateUser($user);
        }

        // Assign the User Groups according to settings
        foreach ($settings->userGroups as $userGroupUid) {
            $userGroupIds = [];

            if ($userGroup = Craft::$app->getUserGroups()->getGroupByUid($userGroupUid)) {
                $userGroupIds[] = $userGroup->id;
            }

            Craft::$app->getUsers()->assignUserToGroups($user->id, $userGroupIds);
        }

        if ($settings->sendActivationEmail) {
            Craft::$app->getUsers()->sendActivationEmail($user);
        }

        // Trigger an `afterRegister` event
        $this->trigger(self::EVENT_AFTER_REGISTER, new UserEvent([
            'user' => $user,
            'userProfile' => $userProfile,
            'provider' => $provider,
        ]));

        return $user;
    }

    private function _createUser(Provider $provider, UserProfile $userProfile): User
    {
        $settings = SocialLogin::$plugin->getSettings();

        $user = new User();
        $user->username = $userProfile->email;
        $user->email = $userProfile->email;

        if ($settings->populateProfile) {
            $user = $this->_syncUserProfile($provider, $user, $userProfile);
        }

        return $user;
    }

    private function _syncUserProfile(Provider $provider, User $user, UserProfile $userProfile): User
    {
        $userFields = $provider->getCraftUserFields();

        foreach (array_filter($provider->fieldMapping) as $attribute => $profile) {
            $value = null;

            try {
                // Get the raw value from the provider. UserProfile smart enough to return `null`.
                $value = $userProfile->$profile;

                // Get the destination field/attribute UserField model to parse mapping
                $userField = ArrayHelper::firstWhere($userFields, 'handle', $attribute) ?? UserField::TYPE_STRING;

                // Get the parsed value
                $value = $this->_getFieldMappingValue($user, $userField, $value);

                if (!$value) {
                    continue;
                }

                $isField = str_starts_with($attribute, 'field:');
                $attribute = str_replace('field:', '', $attribute);

                if ($isField) {
                    $user->setFieldValue($attribute, $value);
                } else {
                    $user->$attribute = $value;
                }
            } catch (Throwable $e) {
                SocialLogin::error('Error mapping field “{field}:{profile}” - “{value}” for “{provider}”: “{message}” {file}:{line}', [
                    'value' => Json::encode($value),
                    'field' => $attribute,
                    'profile' => $profile,
                    'provider' => $provider->handle,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        }

        return $user;
    }

    private function _matchExistingUser(Provider $provider, UserProfile $userProfile): ?User
    {
        $matchUserSource = $provider->matchUserSource;
        $matchUserDestination = $provider->matchUserDestination;

        // Get the value from the profile
        $value = $userProfile->$matchUserSource;

        if (!$value) {
            return null;
        }

        // Find a matching user
        $matchUserDestination = str_replace('field:', '', $matchUserDestination);

        return User::find()->$matchUserDestination($value)->one();
    }    

    private function _getFieldMappingValue(User $user, UserField $userField, mixed $providerValue): ?string
    {
        if ($userField->getType() === UserField::TYPE_FILE_UPLOAD) {
            if ($photoUrl = AssetHelper::fetchRemoteImage($user, $providerValue, 'user-photo')) {
                Craft::$app->getUsers()->saveUserPhoto($photoUrl, $user, basename($photoUrl));
            }

            // Don't map an actual value to the photo field
            return null;
        }

        if (is_array($providerValue)) {
            return Json::encode($providerValue);
        }

        return (string)$providerValue;
    }
}
