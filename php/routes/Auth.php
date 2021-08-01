<?php

declare(strict_types=1);

namespace Phasem\routes;

use Phasem\App;
use Phasem\db\{Accounts, AuthTokens, MfaKeys};
use Phasem\middleware\AllRequests;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use Teapot\{HttpException, StatusCode};

class Auth
{
    public function __invoke(RouteCollectorProxy $app)
    {
        // create a user
        $app->post('/user', function (Request $request, Response $response) {
            $body = $request->getParsedBody();

            return AllRequests::jsonResp($response, [
                'id' => (new Accounts())->insertUserFromApi($body),
            ]);
        });

        // use credentials to get a token
        $app->post('/token', function (Request $request, Response $response) {
            $data = $request->getParsedBody();

            if (!is_array($data) || !isset($data['email'], $data['password'])) {
                throw new HttpException('Missing email and password properties');
            }

            if (!is_string($data['email']) || $data['email'] === '') {
                throw new HttpException('Email must be a non-blank string');
            }

            if (!is_string($data['password']) || $data['password'] === '') {
                throw new HttpException('Password must be a non-blank string');
            }

            // todo: log failed login attempts in database with IP address
            $user = (new Accounts())->getUserByEmail($data['email']);

            if ($user === null || !$user->verifyPassword($data['password'])) {
                throw new HttpException('Invalid login request', StatusCode::UNAUTHORIZED);
            }

            $userAgent = $request->getHeaderLine('User-Agent');
            $token = (new AuthTokens())->insertToken($user, $userAgent);
            $key = (new MfaKeys())->getEnabledMfaKey($user->getId());

            return AllRequests::jsonResp($response, [
                'token' => $token,
                'isMfaEnabled' => $key !== null,
            ]);
        });

        // deactivate a valid token (log out)
        $app->delete('/token', function (Request $request, Response $response) {
            try {
                $authTokens = new AuthTokens();
                $authTokens->validateRequestAuth($request, false);
                $authTokens->deactivateToken(App::getUser()->getAuthId());
                return $response->withStatus(StatusCode::NO_CONTENT);
            } catch (\Exception $e) {
                return AllRequests::jsonResp($response, ['error' => $e->getMessage()])
                    ->withStatus(StatusCode::UNAUTHORIZED);
            }
        });
    }
}
