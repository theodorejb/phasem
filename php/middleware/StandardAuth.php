<?php

declare(strict_types=1);

namespace Phasem\middleware;

use Phasem\db\AuthTokens;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ServerRequestInterface as Request;

class StandardAuth
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        (new AuthTokens())->validateRequestAuth($request, true);
        return $handler->handle($request);
    }
}
