<?php

declare(strict_types=1);

use Phasem\App;
use Phasem\db\ApiRequests;
use Slim\Exception\{HttpMethodNotAllowedException, HttpNotFoundException};
use Psr\Http\Message\ServerRequestInterface as Request;
use Teapot\{HttpException, StatusCode};

return function (
    Request $request,
    Throwable $e,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($app) {
    $user = App::getUser();
    $message = $e->getMessage();
    $headers = [];

    if ($e instanceof HttpException) {
        $status = ($e->getCode() === 0) ? StatusCode::BAD_REQUEST : $e->getCode();
    } elseif ($e instanceof HttpMethodNotAllowedException) {
        $status = StatusCode::METHOD_NOT_ALLOWED;
        $headers['Allow'] = $e->getAllowedMethods();
    } elseif ($e instanceof HttpNotFoundException) {
        $status = StatusCode::NOT_FOUND;
        $message = 'Invalid route ' . $request->getUri()->getPath();
    } else {
        $status = StatusCode::INTERNAL_SERVER_ERROR;
    }

    if ($user !== null) {
        (new ApiRequests())->recordRequest($user, $request, $message);
    } elseif ($logErrors) {
        $logMessage = $message;

        if ($request->getMethod() === 'POST' && $request->getUri()->getPath() === '/api/token') {
            $serverParams = $request->getServerParams();
            $ip = $serverParams['REMOTE_ADDR'] ?? null;

            if ($ip !== null) {
                $logMessage .= ' | ip: ' . $ip;
            }
        }

        // log to standard error log
        $logMessage .= ' | endpoint: ' . $request->getUri()->__toString();
        $logMessage .= ' | method: ' . $request->getMethod();

        if ($logErrorDetails) {
            $body = $request->getParsedBody();

            if ($body !== null) {
                $logMessage .= ' | body: ' . json_encode(App::hashSensitiveKeys($body));
            }
        }

        error_log($logMessage);
    }

    $response = $app->getResponseFactory()->createResponse($status);
    $json = ['error' => $message];

    if ($displayErrorDetails) {
        $json['trace'] = $e->getTraceAsString();
    }

    foreach ($headers as $name => $value) {
        $response = $response->withHeader($name, $value);
    }

    return json_resp($response, $json);
};
