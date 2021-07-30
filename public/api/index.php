<?php

declare(strict_types=1);

use Phasem\App;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

require '../../bootstrap.php';
require '../../api/middleware/auth.php';

$app = AppFactory::create();

// endpoints with standard authorization
$app->group('/api', function (RouteCollectorProxy $app) {
    require '../../api/endpoints/me.php';
    require '../../api/endpoints/two_factor_auth.php';
})->add('standard_auth');

// endpoints without standard authorization
$app->group('/api/auth', function (RouteCollectorProxy $app) {
    require '../../api/endpoints/auth.php';
});

$app->addRoutingMiddleware();
$app->add('all_requests');

$errorMiddleware = $app->addErrorMiddleware(App::getConfig()->isDevEnv(), true, true);
$errorMiddleware->setDefaultErrorHandler(require '../../api/errorHandlers.php');
$app->addBodyParsingMiddleware();

$app->run();
