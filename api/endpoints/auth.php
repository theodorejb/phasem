<?php

declare(strict_types=1);

use Phasem\db\{AuthTokens, Users};
use Slim\Http\{Request, Response};
use Teapot\{HttpException, StatusCode};

// create a user
$app->post('/user', function (Request $request, Response $response) {
    return $response->withJson([
        'id' => (new Users())->insertUserFromApi($request->getParsedBody()),
    ]);
});

// use credentials to get a token
$app->post('/token', function (Request $request, Response $response) {
    $data = $request->getParsedBody();

    if (empty($data['email']) || empty($data['password'])) {
        throw new HttpException('Email and password cannot be blank');
    }

    $user = (new Users())->getUserByEmail($data['email']);

    if ($user === null || !$user->verifyPassword($data['password'])) {
        throw new HttpException('Invalid email/password');
    }

    $token = (new \Phasem\db\AuthTokens())->insertToken($user);

    return $response->withJson(['token' => $token]);
});

// deactivate a valid token (log out)
$app->delete('/token', function (Request $request, Response $response) {
    try {
        $authTokens = new AuthTokens();
        $authId = $authTokens->validateRequestAuth($request, false);
        $authTokens->deactivateToken($authId);
        return $response->withStatus(StatusCode::NO_CONTENT);
    } catch (Exception $e) {
        return $response
            ->withStatus(StatusCode::UNAUTHORIZED)
            ->withJson(['error' => $e->getMessage()]);
    }
});
