<?php

declare(strict_types=1);

use Phasem\db\AuthTokens;
use Slim\Http\{Request, Response};

return function (Request $request, Response $response, callable $next) {
    (new AuthTokens())->validateRequestAuth($request, true);
    return $next($request, $response);
};
