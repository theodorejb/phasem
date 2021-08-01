<?php

declare(strict_types=1);

namespace Phasem\routes;

use Phasem\App;
use Phasem\db\Accounts;
use Phasem\middleware\AllRequests;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use Teapot\StatusCode;

class Me
{
    public function __invoke(RouteCollectorProxy $app)
    {
        $app->get('', function (Request $_req, Response $response) {
            return AllRequests::jsonResp($response, ['data' => App::getUser()]);
        });

        $app->post('/profile', function (Request $request, Response $response) {
            $body = $request->getParsedBody();
            (new Accounts())->updateUserProfile(App::getUser(), $body);
            return $response->withStatus(StatusCode::NO_CONTENT);
        });

        $app->post('/email', function (Request $request, Response $response) {
            $body = $request->getParsedBody();
            (new Accounts())->updateUserEmail(App::getUser(), $body);
            return $response->withStatus(StatusCode::NO_CONTENT);
        });

        $app->post('/password', function (Request $request, Response $response) {
            $body = $request->getParsedBody();
            (new Accounts())->updateUserPassword(App::getUser(), $body);
            return $response->withStatus(StatusCode::NO_CONTENT);
        });
    }
}
