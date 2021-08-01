<?php

declare(strict_types=1);

namespace Phasem\middleware;

use Phasem\App;
use Phasem\db\ApiRequests;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ServerRequestInterface as Request;

class AllRequests
{
    public function __invoke(Request $request, RequestHandler $handler): Response
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

    public static function jsonResp(Response $response, array $data): Response
    {
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
