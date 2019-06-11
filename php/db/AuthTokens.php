<?php

declare(strict_types=1);

namespace Phasem\db;

use DateTime;
use Phasem\App;
use Phasem\model\CurrentUser;
use Phasem\model\User;
use Slim\Http\Request;
use Teapot\{HttpException, StatusCode};

class AuthTokens
{
    const TWO_FACTOR_REQUIRED_ERROR = 'Two-factor authentication code required';

    private $db;

    public function __construct()
    {
        $this->db = DbConnector::getDatabase();
    }

    public function insertToken(User $user, string $userAgent): string
    {
        $token = self::generateToken();
        $parts = self::tokenParts($token);
        $now = (new DateTime())->format(DbConnector::SQL_DATE);
        $userAgentId = null;

        if ($userAgent !== '') {
            $userAgentId = (new UserAgents())->getUserAgentId($userAgent);
        }

        $this->db->insertRow('auth_tokens', [
            'account_id' => $user->getId(),
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
        $set = ['auth_token_deactivated' => (new DateTime())->format(DbConnector::SQL_DATE)];
        $this->db->updateRows('auth_tokens', $set, ['auth_id' => $authId]);
    }

    public function deactivateOtherTokens(CurrentUser $user): void
    {
        $set = ['auth_token_deactivated' => (new DateTime())->format(DbConnector::SQL_DATE)];

        $this->db->updateRows('auth_tokens', $set, [
            'account_id' => $user->getId(),
            'auth_token_deactivated' => ['nu' => ''],
            'auth_id' => ['ne' => $user->getAuthId()],
        ]);
    }

    public function validateRequestAuth(Request $request, bool $autoRenew): void
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader) {
            throw new HttpException('Missing required Authorization header', StatusCode::UNAUTHORIZED);
        }

        $bearer = 'Bearer ';

        if (strpos($authHeader, $bearer) !== 0) {
            throw new HttpException('Authorization header must use Bearer authentication scheme', StatusCode::UNAUTHORIZED);
        }

        $sql = "SELECT t.account_id, t.auth_id, t.verifier, t.auth_token_last_renewed, t.mfa_last_completed, ua.user_agent, (
                    SELECT k.mfa_enabled
                    FROM mfa_keys k
                    WHERE k.account_id = t.account_id
                    AND k.mfa_enabled IS NOT NULL
                    AND k.mfa_disabled IS NULL
                ) AS mfa_enabled
                FROM auth_tokens t
                LEFT JOIN user_agents ua ON ua.user_agent_id = t.user_agent_id
                WHERE t.selector = ?
                AND t.auth_token_deactivated IS NULL";

        $parts = self::tokenParts(substr($authHeader, strlen($bearer)));
        $tokenRow = $this->db->query($sql, [$parts['selector']])->getFirst();

        if ($tokenRow === null || !hash_equals($tokenRow['verifier'], $parts['verifierHash'])) {
            throw new HttpException('Invalid authentication token', StatusCode::UNAUTHORIZED);
        }

        $lastRenewed = new DateTime($tokenRow['auth_token_last_renewed']);

        if ($lastRenewed < new DateTime('3 months ago')) {
            throw new HttpException('Authentication token has expired. Please log in again.', StatusCode::UNAUTHORIZED);
        }

        if ($autoRenew && $lastRenewed < new DateTime('1 minute ago')) {
            $set = [
                'auth_token_last_renewed' => (new DateTime())->format(DbConnector::SQL_DATE),
            ];

            // the user agent for an auth token can change periodically as browser updates are installed
            $userAgent = $request->getHeaderLine('User-Agent');

            if ($userAgent !== '' && UserAgents::trimUserAgent($userAgent) !== $tokenRow['user_agent']) {
                $set['user_agent_id'] = (new UserAgents())->getUserAgentId($userAgent);
            }

            $this->db->updateRows('auth_tokens', $set, ['auth_id' => $tokenRow['auth_id']]);
        }

        $mfaLastCompleted = $tokenRow['mfa_last_completed'] ? new DateTime($tokenRow['mfa_last_completed']) : null;

        if ($tokenRow['mfa_enabled'] !== null && $request->getUri()->getPath() !== 'two_factor_auth/verify') {
            $mfaEnabled = new DateTime($tokenRow['mfa_enabled']);

            // require MFA to be completed if it never was completed, or was completed before MFA was reconfigured
            if (!$mfaLastCompleted || $mfaLastCompleted < $mfaEnabled) {
                // clients should check for this 404 status message and prompt user to enter 2FA code
                throw new HttpException(self::TWO_FACTOR_REQUIRED_ERROR, StatusCode::UNAUTHORIZED);
            }
        }

        $userRow = $this->db->query("SELECT * FROM accounts WHERE account_id = ?", [$tokenRow['account_id']])->getFirst();
        $userRow['auth_id'] = $tokenRow['auth_id'];
        $userRow['mfa_last_completed'] = $mfaLastCompleted;
        App::setUser(new CurrentUser($userRow));
    }

    /**
     * Updates the mfa_last_completed field for the specified token, and returns a new version
     * of the token so that the original token issued before completing MFA will no longer be valid.
     */
    public function updateMfaCompletion(int $authId): string
    {
        $token = self::generateToken();
        $parts = self::tokenParts($token);

        $set = [
            'mfa_last_completed' => date(DbConnector::SQL_DATE),
            'selector' => $parts['selector'],
            'verifier' => $parts['verifierHash'],
        ];

        $this->db->updateRows('auth_tokens', $set, ['auth_id' => $authId]);
        return $token;
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
