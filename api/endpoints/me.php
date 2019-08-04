<?php

declare(strict_types=1);

use Phasem\App;
use Phasem\db\Accounts;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use Teapot\StatusCode;

$app->group('/me', function (RouteCollectorProxy $app) {
    $app->get('', function (Request $request, Response $response) {
        return json_resp($response, ['data' => App::getUser()]);
    });

    $app->post('/profile', function (Request $request, Response $response) {
        (new Accounts())->updateUserProfile(App::getUser(), $request->getParsedBody());
        return $response->withStatus(StatusCode::NO_CONTENT);
    });

    $app->post('/email', function (Request $request, Response $response) {
        (new Accounts())->updateUserEmail(App::getUser(), $request->getParsedBody());
        return $response->withStatus(StatusCode::NO_CONTENT);
    });

    $app->post('/password', function (Request $request, Response $response) {
        (new Accounts())->updateUserPassword(App::getUser(), $request->getParsedBody());
        return $response->withStatus(StatusCode::NO_CONTENT);
    });
});
