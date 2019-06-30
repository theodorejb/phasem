<?php

declare(strict_types=1);

use Phasem\App;
use Phasem\db\ApiRequests;
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

        $user = App::getUser();
        $message = $e->getMessage();

        if ($user !== null) {
            (new ApiRequests())->recordRequest($user, $request, $message);
        } else {
            $body = $request->getParsedBody();

            if ($request->getMethod() === 'POST' && $request->getUri()->getPath() === '/api/token') {
                $serverParams = $request->getServerParams();
                $ip = $serverParams['REMOTE_ADDR'] ?? null;

                if ($ip !== null) {
                    $message .= ' | ip: ' . $ip;
                }
            }

            // log to standard error log
            $message .= ' | endpoint: ' . $request->getUri()->__toString();
            $message .= ' | method: ' . $request->getMethod();

            if ($body !== null) {
                $message .= ' | body: ' . json_encode(App::hashSensitiveKeys($body));
            }

            error_log($message);
        }

        return $response
            ->withStatus($status)
            ->withJson(['error' => $e->getMessage()]);
    };
};
