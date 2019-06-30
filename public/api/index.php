<?php

declare(strict_types=1);

use Phasem\App;

require '../../bootstrap.php';

$container = [
    'settings' => [
        'displayErrorDetails' => App::getConfig()['devMode'],
    ],
];

require '../../api/errorHandlers.php';
require '../../api/middleware/auth.php';

$app = new \Slim\App($container);
$outerMiddleware = require '../../api/middleware/headers.php';
$app->add($outerMiddleware);

// endpoints with standard authorization
$app->group('', function (\Slim\App $app) {
    require '../../api/endpoints/me.php';
    require '../../api/endpoints/two_factor_auth.php';
})->add('standard_auth');

// endpoints without standard authorization
$app->group('/auth', function (\Slim\App $app) {
    require '../../api/endpoints/auth.php';
});

$app->run();
