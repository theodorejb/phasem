<?php

declare(strict_types=1);

use Phasem\db\AuthTokens;
use Slim\Http\{Request, Response};
use Teapot\StatusCode;

return function (Request $request, Response $response, callable $next) {
    try {
        (new AuthTokens())->validateRequestAuth($request, true);
        return $next($request, $response);
    } catch (Exception $e) {
        return $response // don't continue execution
            ->withStatus(StatusCode::UNAUTHORIZED)
            ->withJson(['error' => $e->getMessage()]);
    }
};
