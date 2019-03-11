<?php

declare(strict_types=1);

require '../../bootstrap.php';

$container = [
    'settings' => [
        'displayErrorDetails' => Phasem\App::getConfig()['devMode'],
    ],
];

require '../../api/errorHandlers.php';

$app = new Slim\App($container);
$authMiddleware = require '../../api/middleware/auth.php';

$outerMiddleware = require '../../api/middleware/headers.php';
$app->add($outerMiddleware);

// endpoints with standard authorization
$app->group('', function (\Slim\App $app) {
    require '../../api/endpoints/me.php';
    require '../../api/endpoints/two_factor_auth.php';
})->add($authMiddleware);

// endpoints without standard authorization
$app->group('/auth', function (\Slim\App $app) {
    require '../../api/endpoints/auth.php';
});

$app->run();
