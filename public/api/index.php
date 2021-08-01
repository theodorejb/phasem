<?php

declare(strict_types=1);

use Phasem\App;
use Phasem\middleware\{AllRequests, ErrorHandler, StandardAuth};
use Phasem\routes\{Auth, Me, TwoFactorAuth};
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

require '../../bootstrap.php';

$app = AppFactory::create();

// endpoints with standard authorization
$app->group('/api', function (RouteCollectorProxy $app) {
    $app->group('/me', Me::class);
    $app->group('/two_factor_auth', TwoFactorAuth::class);
})->add(StandardAuth::class);

// endpoints without standard authorization
$app->group('/api/auth', Auth::class);

$app->addRoutingMiddleware();
$app->add(AllRequests::class);

$errorMiddleware = $app->addErrorMiddleware(App::getConfig()->isDevEnv(), true, true);
$errorMiddleware->setDefaultErrorHandler(ErrorHandler::getHandler($app));
$app->addBodyParsingMiddleware();

$app->run();
