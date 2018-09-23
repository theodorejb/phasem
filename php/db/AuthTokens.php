<?php

declare(strict_types=1);

namespace Phasem\db;

use Phasem\App;
use Phasem\model\User;
use Slim\Http\Request;
use Teapot\{HttpException, StatusCode};

class AuthTokens
{
    private $db;

    public function __construct()
    {
        $this->db = DbConnector::getDatabase();
    }

    public function insertToken(User $user, string $userAgent): string
    {
        $token = self::generateToken();
        $parts = self::tokenParts($token);
        $now = (new \DateTime())->format(DbConnector::SQL_DATE);
        $userAgentId = null;

        if ($userAgent !== '') {
            $userAgentId = (new UserAgents())->getUserAgentId($userAgent);
        }

        $this->db->insertRow('auth_tokens', [
            'user_id' => $user->getId(),
            'selector' => $parts['selector'],
            'verifier' => $parts['verifierHash'],
            'auth_token_created' => $now,
            'auth_token_last_renewed' => $now,
            'auth_token_renew_count' => 0,
            'user_agent_id' => $userAgentId,
        ]);

        return $token;
    }

    public function deactivateToken(int $authId): void
    {
        $set = ['auth_token_deactivated' => (new \DateTime())->format(DbConnector::SQL_DATE)];
        $this->db->updateRows('auth_tokens', $set, ['auth_id' => $authId]);
    }

    /**
     * Returns the auth token ID if the request has a valid authorization header
     */
    public function validateRequestAuth(Request $request, bool $autoRenew): int
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader) {
            throw new \Exception('Missing required Authorization header');
        }

        return $this->validateAuthHeader($authHeader, $autoRenew);
    }

    private function validateAuthHeader(string $authHeader, bool $autoRenew): int
    {
        $bearer = 'Bearer ';

        if (strpos($authHeader, $bearer) !== 0) {
            throw new HttpException('Authorization header must use Bearer authentication scheme', StatusCode::UNAUTHORIZED);
        }

        $sql = "SELECT user_id, auth_id, verifier, auth_token_last_renewed
                FROM auth_tokens
                WHERE selector = ?
                AND auth_token_deactivated IS NULL";

        $parts = self::tokenParts(substr($authHeader, strlen($bearer)));
        $tokenRow = $this->db->query($sql, [$parts['selector']])->getFirst();

        if ($tokenRow === null || !hash_equals($tokenRow['verifier'], $parts['verifierHash'])) {
            throw new HttpException('Invalid authentication token', StatusCode::UNAUTHORIZED);
        }

        $lastRenewed = new \DateTime($tokenRow['auth_token_last_renewed']);

        if ($lastRenewed < new \DateTime('1 month ago')) {
            throw new HttpException('Authentication token has expired. Please log in again.', StatusCode::UNAUTHORIZED);
        }

        if ($autoRenew && $lastRenewed < new \DateTime('1 day ago')) {
            $set = ['auth_token_last_renewed' => (new \DateTime())->format(DbConnector::SQL_DATE)];
            $this->db->updateRows('auth_tokens', $set, ['auth_id' => $tokenRow['auth_id']]);
        }

        $userRow = $this->db->query("SELECT * FROM users WHERE user_id = ?", [$tokenRow['user_id']])->getFirst();
        App::setUser(new User($userRow));
        return $tokenRow['auth_id'];
    }

    private static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private static function tokenParts(string $token): array
    {
        if (strlen($token) !== 64) {
            throw new HttpException('Invalid authentication token', StatusCode::UNAUTHORIZED);
        }

        [$selector, $verifier] = [substr($token, 0, 32), substr($token, 32)];

        return [
            'selector' => $selector,
            'verifier' => $verifier,
            'verifierHash' => hash('sha256', $verifier, true),
        ];
    }
}
