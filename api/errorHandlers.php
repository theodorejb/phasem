<?php

declare(strict_types=1);

namespace Phasem\api;

use Slim\Http\{Request, Response};
use Teapot\{HttpException, StatusCode};

$container['notFoundHandler'] = function ()  {
    return function (Request $request, Response $response) {
        return $response
            ->withStatus(StatusCode::NOT_FOUND)
            ->withJson([
                'error' => 'Invalid route ' . $request->getUri()->getPath(),
            ]);
    };
};

$container['notAllowedHandler'] = function () {
    return function (Request $request, Response $response, array $methods) {
        $allowedMethods = implode(', ', $methods);

        return $response
            ->withStatus(StatusCode::METHOD_NOT_ALLOWED)
            ->withHeader('Allow', $allowedMethods)
            ->withJson([
                'error' => "Method must be one of: {$allowedMethods}",
            ]);
    };
};

$container['errorHandler'] = function () {
    return function (Request $request, Response $response, \Exception $e) {
        if ($e instanceof HttpException) {
            $status = ($e->getCode() === 0) ? StatusCode::BAD_REQUEST : $e->getCode();
        } else {
            $status = StatusCode::INTERNAL_SERVER_ERROR;
        }

        return $response
            ->withStatus($status)
            ->withJson(['error' => $e->getMessage()]);
    };
};
