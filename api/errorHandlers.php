<?php

declare(strict_types=1);

namespace Phasem\api;

use Phasem\App;
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

        $message = $e->getMessage();
        $serverParams = $request->getServerParams();

        if (!empty($serverParams['REMOTE_ADDR'])) {
            $message .= ' | ip: ' . $serverParams['REMOTE_ADDR'];
        }

        $headers = $request->getHeaders();
        $ignore = [
            'CONTENT_LENGTH',
            'HTTP_ACCEPT_LANGUAGE',
            'HTTP_ACCEPT_ENCODING',
            'HTTP_ACCEPT',
            'HTTP_AUTHORIZATION',
            'HTTP_CONNECTION',
            'HTTP_COOKIE',
            'HTTP_DNT',
        ];

        foreach ($ignore as $header) {
            unset($headers[$header]);
        }

        if (count($headers) !== 0) {
            $message .= ' | headers: ' . json_encode($headers);
        }

        $cookies = $request->getCookieParams();
        unset($cookies['ApiAuth']);

        if (count($cookies) !== 0) {
            $message .= ' | cookies: ' . json_encode($cookies);
        }

        $body = $request->getBody()->getContents();
        $jsonType = 'application/json';

        // if the body has a JSON content-type, and the JSON contains a password property, don't log the password
        if (substr($request->getContentType(), 0, strlen($jsonType)) === $jsonType) {
            $decoded = json_decode($body);

            if (is_object($decoded)) {
                // hash value of any top-level property with "password" in its name

                foreach ($decoded as $prop => $val) {
                    if (strpos(strtolower($prop), 'password') !== false) {
                        $decoded->$prop = sha1($val);
                    }
                }
            }

            $body = json_encode($decoded);
        }

        $message .= ' | endpoint: ' . $request->getUri()->__toString();
        $message .= ' | method: ' . $request->getMethod();
        $message .= ' | body: ' . $body;
        $user = App::getUser();

        if ($user !== null) {
            $message .= ' | user: ' . $user->getEmail() . ' (#' . $user->getId() . ')';
        }

        error_log($message);

        return $response
            ->withStatus($status)
            ->withJson(['error' => $e->getMessage()]);
    };
};
