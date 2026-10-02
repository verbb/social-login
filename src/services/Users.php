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
use craft\helpers\Db;
use craft\helpers\Json;

use RuntimeException;
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

    public function loginOrRegisterUser(Provider $provider, Token $token, ?int $initiatingUserId = null, bool $isConnect = false): bool
    {
        if (!$isConnect && !$provider::supportsLogin()) {
            SocialLogin::error('Provider “{provider}” cannot be used to log in.', ['provider' => $provider->handle]);

            return false;
        }

        $userProfile = $provider->getUserProfile($token);
        $identifier = $this->_profileIdentifier($userProfile->id);

        if ($identifier === null) {
            SocialLogin::error('Provider “{provider}” did not return a stable account identifier.', ['provider' => $provider->handle]);

            return false;
        }

        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser && $initiatingUserId && $currentUser->id !== $initiatingUserId) {
            SocialLogin::error('OAuth login was started by a different Craft user.');

            return false;
        }

        $isNewUser = false;

        if ($isConnect) {
            if (!$currentUser || !$initiatingUserId || $currentUser->id !== $initiatingUserId) {
                SocialLogin::error('A provider connection must be completed by the Craft user who started it.');

                return false;
            }

            $user = $currentUser;
        } else {
            $connections = SocialLogin::$plugin->getConnections()->getAllConnectionsByProviderIdentifier($provider->handle, $identifier);

            if (count($connections) > 1) {
                SocialLogin::error('Provider identity “{provider}:{identifier}” has conflicting Craft user connections.', [
                    'provider' => $provider->handle,
                    'identifier' => $identifier,
                ]);

                return false;
            }

            if ($connections) {
                $user = $connections[0]->getUser();
            } else {
                if ($currentUser || $initiatingUserId) {
                    SocialLogin::error('Use the connect action to link a provider to a signed-in Craft user.');

                    return false;
                }

                $result = $this->_getOrCreateUser($provider, $userProfile);

                if (!$result) {
                    return false;
                }

                [$user, $isNewUser] = $result;
            }
        }

        if (!$user) {
            SocialLogin::error('Unable to find the Craft user for this provider connection.');

            return false;
        }

        if (!$isNewUser && !$this->_canLogin($user)) {
            $this->_logBlockedUser($user);

            return false;
        }

        $settings = SocialLogin::$plugin->getSettings();

        if (!$isNewUser && $settings->populateProfile && $settings->syncProfile) {
            $this->_syncUserProfile($provider, $user, $userProfile);

            if (!Craft::$app->getElements()->saveElement($user)) {
                SocialLogin::error('Unable to synchronize the Craft user profile.');

                return false;
            }
        }

        if (!$isConnect && ($loginName = Session::get('loginName'))) {
            $resumingUser = Craft::$app->getUsers()->getUserByUsernameOrEmail($loginName);

            if (!$resumingUser || $resumingUser->id !== $user->id) {
                SocialLogin::error('Tried to resume session for “{expected}”, but authenticated as “{actual}”.', [
                    'expected' => $resumingUser?->email ?? $loginName,
                    'actual' => $user->email,
                ]);

                return false;
            }
        }

        $connection = new Connection([
            'userId' => $user->id,
            'providerHandle' => $provider->handle,
            'identifier' => $identifier,
        ]);

        if (!SocialLogin::$plugin->getConnections()->upsertConnection($connection, $token)) {
            SocialLogin::error('Unable to save login connection.');

            return false;
        }

        if ($isConnect) {
            return true;
        }

        if (!$this->_canLogin($user)) {
            Session::setError('social-login', Craft::t('social-login', 'Your account must be activated before you can sign in.'));
            $this->_logBlockedUser($user);

            return false;
        }

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
        $duration = $rememberMe && $generalConfig->rememberedUserSessionDuration !== 0
            ? $generalConfig->rememberedUserSessionDuration
            : $generalConfig->userSessionDuration;

        if (!Craft::$app->getUser()->login($user, $duration)) {
            Session::setError('social-login', Craft::t('social-login', 'Unable to login.'));

            return false;
        }

        $this->trigger(self::EVENT_AFTER_LOGIN, new UserEvent([
            'user' => $user,
            'userProfile' => $userProfile,
            'provider' => $provider,
        ]));

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _getOrCreateUser(Provider $provider, UserProfile $userProfile): ?array
    {
        $user = $this->_matchExistingUser($provider, $userProfile);

        if ($user) {
            return [$user, false];
        }

        $settings = SocialLogin::$plugin->getSettings();

        if (!$settings->enableRegistration) {
            SocialLogin::error('User registration is disabled and no existing provider connection was found.');

            return null;
        }

        Craft::$app->requireEdition(Craft::Pro);

        $user = $this->_createUser($provider, $userProfile);
        $event = new UserEvent([
            'user' => $user,
            'userProfile' => $userProfile,
            'provider' => $provider,
        ]);

        $this->trigger(self::EVENT_BEFORE_REGISTER, $event);

        if (!$event->isValid) {
            SocialLogin::error('User registration cancelled by event.');

            return null;
        }

        $user = $event->user;

        if (!$user->email) {
            SocialLogin::error('Provider “{provider}” does not support emails, unable to create user.', ['provider' => $provider->handle]);

            return null;
        }

        if (!Craft::$app->getElements()->saveElement($user)) {
            $error = Craft::t('social-login', 'Unable to register user: {json}.', ['json' => Json::encode($user->getErrors())]);
            Session::setError('social-login', $error);
            SocialLogin::error($error);

            return null;
        }

        if ($settings->forceActivate && $userProfile->getEmailVerified() === true) {
            Craft::$app->getUsers()->activateUser($user);
        }

        $userGroupIds = [];

        foreach ($settings->userGroups as $userGroupUid) {
            if ($userGroup = Craft::$app->getUserGroups()->getGroupByUid($userGroupUid)) {
                $userGroupIds[] = $userGroup->id;
            }
        }

        Craft::$app->getUsers()->assignUserToGroups($user->id, $userGroupIds);

        if ($settings->sendActivationEmail && !$this->_canLogin($user)) {
            Craft::$app->getUsers()->sendActivationEmail($user);
        }

        $this->trigger(self::EVENT_AFTER_REGISTER, new UserEvent([
            'user' => $user,
            'userProfile' => $userProfile,
            'provider' => $provider,
        ]));

        return [$user, true];
    }

    private function _createUser(Provider $provider, UserProfile $userProfile): User
    {
        $settings = SocialLogin::$plugin->getSettings();
        $email = $this->_profileString($userProfile->email);
        $user = new User([
            'username' => $email,
            'email' => $email,
        ]);

        if ($settings->populateProfile) {
            $this->_syncUserProfile($provider, $user, $userProfile);
        }

        if (Session::get('isCpRequest') && $provider->canRegisterAdmin($userProfile)) {
            $user->admin = true;
        }

        return $user;
    }

    private function _syncUserProfile(Provider $provider, User $user, UserProfile $userProfile): User
    {
        $userFields = $provider->getCraftUserFields();

        foreach (array_filter($provider->fieldMapping) as $attribute => $profile) {
            $value = null;

            try {
                if ($attribute === 'email' && $userProfile->getEmailVerified() !== true) {
                    continue;
                }

                $value = $userProfile->$profile;
                $userField = ArrayHelper::firstWhere($userFields, 'handle', $attribute) ?? UserField::TYPE_STRING;
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
                SocialLogin::error('Error mapping field “{field}:{profile}” for “{provider}”: {exception} {file}:{line}', [
                    'field' => $attribute,
                    'profile' => $profile,
                    'provider' => $provider->handle,
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        }

        return $user;
    }

    private function _matchExistingUser(Provider $provider, UserProfile $userProfile): ?User
    {
        $source = $provider->matchUserSource;

        if ($source === 'email' && $userProfile->getEmailVerified() !== true) {
            SocialLogin::info('Skipping initial email match for “{provider}” because the provider did not verify the email address.', ['provider' => $provider->handle]);

            return null;
        }

        if (!in_array($source, ['email', 'id'], true)) {
            throw new RuntimeException("Provider {$provider->handle} cannot automatically match Craft users by $source.");
        }

        $value = $source === 'id'
            ? $this->_profileIdentifier($userProfile->id)
            : $this->_profileString($userProfile->email);

        if ($value === null) {
            return null;
        }

        $destination = $provider->matchUserDestination;
        $allowedDestinations = array_map(fn(UserField $field) => $field->handle, $provider->getCraftUserFields());

        if (!in_array($destination, $allowedDestinations, true)) {
            throw new RuntimeException("Provider {$provider->handle} has an invalid Craft user match destination.");
        }

        $destinationHandle = str_replace('field:', '', $destination);
        $query = User::find()
            ->status(null)
            ->$destinationHandle(Db::escapeParam($value));

        if ($source === 'id') {
            $users = array_values(array_filter($query->all(), function(User $user) use ($destination, $destinationHandle, $value) {
                $candidate = str_starts_with($destination, 'field:')
                    ? $user->getFieldValue($destinationHandle)
                    : $user->$destinationHandle;

                $isScalarIdentifier = is_string($candidate) || is_int($candidate) || (is_float($candidate) && is_finite($candidate));

                return $isScalarIdentifier && (string)$candidate === $value;
            }));
        } else {
            $users = $query->limit(2)->all();
        }

        if (count($users) > 1) {
            throw new RuntimeException("Provider {$provider->handle} matched more than one Craft user.");
        }

        return $users[0] ?? null;
    }

    private function _canLogin(User $user): bool
    {
        return $user->getStatus() === User::STATUS_ACTIVE && !$user->locked && !$user->passwordResetRequired;
    }

    private function _logBlockedUser(User $user): void
    {
        SocialLogin::error('User “{email}” is not allowed to login. status: {status}, isLocked: {locked}, passwordResetRequired: {passwordResetRequired}', [
            'email' => $user->email,
            'status' => $user->getStatus(),
            'locked' => $user->locked,
            'passwordResetRequired' => $user->passwordResetRequired,
        ]);
    }

    private function _profileString(mixed $value): ?string
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $value = trim((string)$value);

        return $value !== '' ? $value : null;
    }

    private function _profileIdentifier(mixed $value): ?string
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $value = (string)$value;

        return trim($value) !== '' ? $value : null;
    }

    private function _getFieldMappingValue(User $user, UserField $userField, mixed $providerValue): ?string
    {
        if ($userField->getType() === UserField::TYPE_FILE_UPLOAD) {
            if ($photoUrl = AssetHelper::fetchRemoteImage($user, $providerValue, 'user-photo')) {
                Craft::$app->getUsers()->saveUserPhoto($photoUrl, $user, basename($photoUrl));
            }

            return null;
        }

        if (is_array($providerValue)) {
            return Json::encode($providerValue);
        }

        return (string)$providerValue;
    }
}
