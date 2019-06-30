<?php

declare(strict_types=1);

use Phasem\App;
use Phasem\db\{Accounts, AuthTokens, MfaKeys};
use Slim\Http\{Request, Response};
use Teapot\{HttpException, StatusCode};

// create a user
$app->post('/user', function (Request $request, Response $response) {
    return $response->withJson([
        'id' => (new Accounts())->insertUserFromApi($request->getParsedBody()),
    ]);
});

// use credentials to get a token
$app->post('/token', function (Request $request, Response $response) {
    $data = $request->getParsedBody();

    if (empty($data['email']) || empty($data['password'])) {
        throw new HttpException('Email and password cannot be blank');
    }

    // todo: log failed login attempts in database with IP address
    $user = (new Accounts())->getUserByEmail($data['email']);

    if ($user === null || !$user->verifyPassword($data['password'])) {
        throw new HttpException('Invalid login request', StatusCode::UNAUTHORIZED);
    }

    $userAgent = $request->getHeaderLine('User-Agent');
    $token = (new AuthTokens())->insertToken($user, $userAgent);
    $key = (new MfaKeys())->getEnabledMfaKey($user->getId());

    return $response->withJson([
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
    } catch (Exception $e) {
        return $response
            ->withStatus(StatusCode::UNAUTHORIZED)
            ->withJson(['error' => $e->getMessage()]);
    }
});
