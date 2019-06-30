<?php

declare(strict_types=1);

use Phasem\App;
use Phasem\db\AuthTokens;
use Slim\Http\{Request, Response};
use Teapot\{HttpException, StatusCode};

function standard_auth(Request $request, Response $response, callable $next)
{
    (new AuthTokens())->validateRequestAuth($request, true);
    return $next($request, $response);
}

/**
 * Require user to have recently entered a valid two-factor authentication code
 */
function recent_mfa_completion(Request $request, Response $response, callable $next)
{
    $user = App::getUser();

    if (!$user) {
        throw new Exception('Authorization must be completed first');
    }

    $mfaLastCompleted = $user->getMfaLastCompleted();

    if ($mfaLastCompleted === null || $mfaLastCompleted < new DateTime('15 minutes ago')) {
        throw new HttpException(AuthTokens::TWO_FACTOR_REQUIRED_ERROR, StatusCode::UNAUTHORIZED);
    }

    return $next($request, $response);
}
