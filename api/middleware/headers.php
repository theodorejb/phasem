<?php

declare(strict_types=1);

use Phasem\App;
use Phasem\db\ApiRequests;
use Slim\Http\{Request, Response};

return function (Request $request, Response $response, callable $next) {
    $response = $next(
        $request,
        $response->withHeader('Cache-Control', 'no-cache') // avoid caching API responses
    );

    $user = App::getUser();

    if ($user !== null) {
        (new ApiRequests())->recordRequest($user, $request);
    }

    return $response;
};
