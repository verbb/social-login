<?php

use verbb\sociallogin\controllers\AuthController;

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
require dirname(__DIR__, 2) . '/src/controllers/AuthController.php';

final class DisconnectRequestFixtureRequest extends craft\web\Request
{
    public bool $csrfValid = true;
    public bool $post = true;

    public function init(): void
    {
    }

    public function getIsCpRequest(): bool
    {
        return false;
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

final class DisconnectRequestFixtureUserSession
{
    public function getIsGuest(): bool
    {
        return false;
    }
}

final class DisconnectRequestFixtureApp extends yii\base\Component
{
    public string $charset = 'UTF-8';
    public string $language = 'en';
    public string $sourceLanguage = 'en';
    public DisconnectRequestFixtureRequest $request;
    public Response $response;
    public DisconnectRequestFixtureUserSession $user;

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

    public function getRequest(): DisconnectRequestFixtureRequest
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getUser(): DisconnectRequestFixtureUserSession
    {
        return $this->user;
    }
}

function disconnectRequestFixtureAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function disconnectRequestFixtureController(bool $post = true, bool $csrfValid = true): AuthController
{
    $app = new DisconnectRequestFixtureApp();
    $app->request = new DisconnectRequestFixtureRequest();
    $app->request->post = $post;
    $app->request->csrfValid = $csrfValid;
    $app->user = new DisconnectRequestFixtureUserSession();
    Craft::$app = $app;
    $app->response = new Response();

    return new AuthController('fixture', new Module('fixture'), [
        'request' => $app->request,
        'response' => $app->response,
    ]);
}

$controller = disconnectRequestFixtureController(false);

try {
    $controller->actionDisconnect();
    throw new RuntimeException('Disconnect must require POST.');
} catch (yii\web\MethodNotAllowedHttpException) {
}

$controller = disconnectRequestFixtureController(true, false);

try {
    $controller->beforeAction(new Action('disconnect', $controller));
    throw new RuntimeException('Disconnect must reject an invalid CSRF token.');
} catch (HttpException $exception) {
    disconnectRequestFixtureAssert($exception->statusCode === 400, 'Invalid CSRF tokens must produce a bad request response.');
}

$controller = disconnectRequestFixtureController();
disconnectRequestFixtureAssert(
    $controller->beforeAction(new Action('disconnect', $controller)),
    'A signed-in POST request with valid CSRF protection must reach the disconnect action.',
);

$sidebar = file_get_contents(dirname(__DIR__, 2) . '/src/templates/_includes/sidebar-pane.html');
disconnectRequestFixtureAssert(str_contains($sidebar, 'data-form="false"'), 'The bundled disconnect control must use an isolated Craft form submission.');
disconnectRequestFixtureAssert(str_contains($sidebar, 'data-action="social-login/auth/disconnect"'), 'The bundled disconnect control must submit to the disconnect action.');
disconnectRequestFixtureAssert(str_contains($sidebar, 'data-param="provider"'), 'The bundled disconnect control must submit the provider handle.');
disconnectRequestFixtureAssert(!str_contains($sidebar, 'href="{{ craft.socialLogin.getDisconnectUrl'), 'The bundled disconnect control must not navigate to the disconnect action.');

$connectingDocs = file_get_contents(dirname(__DIR__, 2) . '/docs/feature-tour/connecting.md');
disconnectRequestFixtureAssert(!str_contains($connectingDocs, '<a href="{{ craft.socialLogin.getDisconnectUrl'), 'Disconnect documentation must not present the action URL as a link.');
disconnectRequestFixtureAssert(str_contains($connectingDocs, 'method="POST"'), 'Disconnect documentation must use a POST form.');
disconnectRequestFixtureAssert(str_contains($connectingDocs, '{{ csrfInput() }}'), 'Disconnect documentation must include CSRF protection.');

echo "Disconnect request security fixture passed.\n";
