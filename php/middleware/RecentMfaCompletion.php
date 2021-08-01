<?php

declare(strict_types=1);

namespace Phasem\middleware;

use Phasem\App;
use Phasem\db\AuthTokens;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ServerRequestInterface as Request;
use Teapot\{HttpException, StatusCode};

/**
 * Require user to have recently entered a valid two-factor authentication code
 */
class RecentMfaCompletion
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $user = App::getUserOrNull();

        if ($user === null) {
            throw new \Exception('Authorization must be completed first');
        }

        $mfaLastCompleted = $user->getMfaLastCompleted();

        if ($mfaLastCompleted === null || $mfaLastCompleted < new \DateTime('15 minutes ago')) {
            throw new HttpException(AuthTokens::TWO_FACTOR_REQUIRED_ERROR, StatusCode::UNAUTHORIZED);
        }

        return $handler->handle($request);
    }
}
