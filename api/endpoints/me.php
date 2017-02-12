<?php

declare(strict_types=1);

use Slim\Http\{Request, Response};

$app->get('/me', function (Request $request, Response $response) {
    return $response->withJson(['data' => Phasem\App::getUser()]);
});
