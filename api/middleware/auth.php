<?php

declare(strict_types=1);

use Phasem\App;
use Phasem\db\{ApiRequests, AuthTokens};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ServerRequestInterface as Request;
use Teapot\{HttpException, StatusCode};

function json_resp(Response $response, array $data): Response
{
    $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
    return $response->withHeader('Content-Type', 'application/json');
}

function all_requests(Request $request, RequestHandler $handler): Response
{
    $response = $handler->handle($request);

    if (!$response->hasHeader('Cache-Control')) {
        // most API responses shouldn't be cached
        $response = $response->withHeader('Cache-Control', 'no-cache');
    }

    $user = App::getUserOrNull();

    if ($user !== null) {
        (new ApiRequests())->recordRequest($user, $request);
    }

    return $response;
}

function standard_auth(Request $request, RequestHandler $handler): Response
{
    (new AuthTokens())->validateRequestAuth($request, true);
    return $handler->handle($request);
}

/**
 * Require user to have recently entered a valid two-factor authentication code
 */
function recent_mfa_completion(Request $request, RequestHandler $handler): Response
{
    $user = App::getUserOrNull();

    if ($user === null) {
        throw new Exception('Authorization must be completed first');
    }

    $mfaLastCompleted = $user->getMfaLastCompleted();

    if ($mfaLastCompleted === null || $mfaLastCompleted < new DateTime('15 minutes ago')) {
        throw new HttpException(AuthTokens::TWO_FACTOR_REQUIRED_ERROR, StatusCode::UNAUTHORIZED);
    }

    return $handler->handle($request);
}
