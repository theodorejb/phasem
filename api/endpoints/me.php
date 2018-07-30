<?php

declare(strict_types=1);

use Phasem\db\Users;
use Slim\Http\{Request, Response};
use Teapot\StatusCode;

$app->group('/me', function (\Slim\App $app) {
    $app->get('', function (Request $request, Response $response) {
        return $response->withJson(['data' => Phasem\App::getUser()]);
    });

    $app->post('/profile', function (Request $request, Response $response) {
        (new Users())->updateUserProfile(\Phasem\App::getUser(), $request->getParsedBody());
        return $response->withStatus(StatusCode::NO_CONTENT);
    });

    $app->post('/email', function (Request $request, Response $response) {
        (new Users())->updateUserEmail(\Phasem\App::getUser(), $request->getParsedBody());
        return $response->withStatus(StatusCode::NO_CONTENT);
    });

    $app->post('/password', function (Request $request, Response $response) {
        (new Users())->updateUserPassword(\Phasem\App::getUser(), $request->getParsedBody());
        return $response->withStatus(StatusCode::NO_CONTENT);
    });
});
