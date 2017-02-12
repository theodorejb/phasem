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

// endpoints with standard authorization
$app->group('', function () use ($app) {
    require '../../api/endpoints/me.php';
})->add($authMiddleware);

// endpoints without standard authorization
$app->group('/auth', function () use ($app) {
    require '../../api/endpoints/auth.php';
});

$app->run();
