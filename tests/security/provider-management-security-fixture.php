<?php

use verbb\sociallogin\controllers\ProvidersController;

use yii\base\Action;
use yii\base\Module;
use yii\web\HttpException;
use yii\web\Response;

$vendorPath = getenv('VERBB_SOCIAL_LOGIN_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';

if (!is_file($vendorPath . '/autoload.php')) {
    throw new RuntimeException('Set VERBB_SOCIAL_LOGIN_TEST_VENDOR to a Craft 5 vendor directory.');
}

require $vendorPath . '/autoload.php';
require $vendorPath . '/yiisoft/yii2/Yii.php';
require $vendorPath . '/craftcms/cms/src/Craft.php';
require dirname(__DIR__, 2) . '/src/controllers/ProvidersController.php';

final class ProviderManagementFixtureRequest extends craft\web\Request
{
    public bool $cp = true;
    public bool $csrfValid = true;
    public bool $post = true;

    public function init(): void
    {
    }

    public function getIsCpRequest(): bool
    {
        return $this->cp;
    }

    public function getIsLivePreview(): bool
    {
        return false;
    }

    public function getIsPost(): bool
    {
        return $this->post;
    }

    public function hasValidSiteToken(): bool
    {
        return false;
    }

    public function validateCsrfToken($clientSuppliedToken = null): bool
    {
        return $this->csrfValid;
    }
}

final class ProviderManagementFixtureSession
{
    public bool $admin = false;
    public bool $guest = false;
    public array $permissions = ['accessCp'];

    public function checkPermission(string $permission): bool
    {
        return $this->admin || in_array($permission, $this->permissions, true);
    }

    public function getIsAdmin(): bool
    {
        return $this->admin;
    }

    public function getIsGuest(): bool
    {
        return $this->guest;
    }

    public function loginRequired(): void
    {
        throw new yii\web\ForbiddenHttpException('Fixture guest denied.');
    }
}

final class ProviderManagementFixtureApp extends yii\base\Component
{
    public bool $allowAdminChanges = true;
    public string $charset = 'UTF-8';
    public string $language = 'en';
    public string $sourceLanguage = 'en';
    public ProviderManagementFixtureRequest $request;
    public Response $response;
    public ProviderManagementFixtureSession $session;

    public function getConfig(): object
    {
        return new class($this->allowAdminChanges) {
            public function __construct(private bool $allowAdminChanges)
            {
            }

            public function getGeneral(): object
            {
                return (object)['allowAdminChanges' => $this->allowAdminChanges];
            }
        };
    }

    public function getErrorHandler(): object
    {
        return (object)['exception' => null];
    }

    public function getI18n(): yii\i18n\I18N
    {
        return new yii\i18n\I18N();
    }

    public function getIsLive(): bool
    {
        return true;
    }

    public function getRequest(): ProviderManagementFixtureRequest
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getUser(): ProviderManagementFixtureSession
    {
        return $this->session;
    }
}

function providerManagementFixtureAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function providerManagementDispatch(string $actionId, bool $admin, bool $allowAdminChanges, bool $cp = true, bool $csrfValid = true): string
{
    $app = new ProviderManagementFixtureApp();
    $app->allowAdminChanges = $allowAdminChanges;
    $app->request = new ProviderManagementFixtureRequest();
    $app->request->cp = $cp;
    $app->request->csrfValid = $csrfValid;
    $app->session = new ProviderManagementFixtureSession();
    $app->session->admin = $admin;
    Craft::$app = $app;
    $app->response = new Response();

    $controller = new ProvidersController('fixture', new Module('fixture'), [
        'request' => $app->request,
        'response' => $app->response,
    ]);

    try {
        return $controller->beforeAction(new Action($actionId, $controller)) ? 'reachable' : 'stopped';
    } catch (HttpException $exception) {
        return (string)$exception->statusCode;
    }
}

foreach (['index', 'edit', 'save'] as $actionId) {
    providerManagementFixtureAssert(
        providerManagementDispatch($actionId, false, true) === '403',
        "A non-administrator must not reach the $actionId provider action.",
    );
    providerManagementFixtureAssert(
        providerManagementDispatch($actionId, true, true, false) === '400',
        "The $actionId provider action must require a control panel request.",
    );
}

foreach (['index', 'edit'] as $actionId) {
    providerManagementFixtureAssert(
        providerManagementDispatch($actionId, true, false) === 'reachable',
        "A read-only administrator may inspect the $actionId provider action.",
    );
}

providerManagementFixtureAssert(
    providerManagementDispatch('save', true, false) === '403',
    'A read-only administrator must not save provider settings.',
);
providerManagementFixtureAssert(
    providerManagementDispatch('save', true, true) === 'reachable',
    'A writable administrator may reach the provider save action.',
);
providerManagementFixtureAssert(
    providerManagementDispatch('future-action', true, false) === '403',
    'An unclassified future action must require administrative changes to be enabled.',
);
providerManagementFixtureAssert(
    providerManagementDispatch('future-action', false, true) === '403',
    'A non-administrator must not reach an unclassified future action.',
);
providerManagementFixtureAssert(
    providerManagementDispatch('save', true, true, true, false) === '400',
    'Provider mutation must reject an invalid CSRF token.',
);

$app = new ProviderManagementFixtureApp();
$app->request = new ProviderManagementFixtureRequest();
$app->request->post = false;
$app->session = new ProviderManagementFixtureSession();
$app->session->admin = true;
Craft::$app = $app;
$app->response = new Response();

$controller = new ProvidersController('fixture', new Module('fixture'), [
    'request' => $app->request,
    'response' => $app->response,
]);

try {
    $controller->actionSave();
    throw new RuntimeException('Provider mutation must require POST.');
} catch (yii\web\MethodNotAllowedHttpException) {
}

echo "Provider management security fixture passed.\n";
