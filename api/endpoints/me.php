<?php

declare(strict_types=1);

use Phasem\db\Users;
use Slim\Http\{Request, Response};
use Teapot\StatusCode;

$app->get('/me', function (Request $request, Response $response) {
    return $response->withJson(['data' => Phasem\App::getUser()]);
});

$app->patch('/me', function (Request $request, Response $response) {
    $user = \Phasem\App::getUser();
    (new Users())->updateUserFromApi($user->getId(), $request->getParsedBody());
    return $response->withStatus(StatusCode::NO_CONTENT);
});
