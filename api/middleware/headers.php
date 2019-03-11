<?php

declare(strict_types=1);

use Slim\Http\{Request, Response};

return function (Request $request, Response $response, callable $next) {
    $response = $next(
        $request,
        $response->withHeader('Cache-Control', 'no-cache') // avoid caching API responses
    );

    $user = \Phasem\App::getUser();

    if ($user !== null) {
        (new \Phasem\db\ApiRequests())->recordRequest($user, $request);
    }

    return $response;
};
