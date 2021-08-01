<?php

declare(strict_types=1);

namespace Phasem\db;

use Phasem\model\{CurrentUser, User};
use Teapot\{HttpException, StatusCode};
use PeachySQL\PeachySql;

/**
 * @psalm-import-type UserRow from \Phasem\model\User
 */
class Accounts
{
    private PeachySql $db;

    public function __construct()
    {
        $this->db = DbConnector::getDatabase();
    }

    public function insertUserFromApi(array|object|null $data): int
    {
        if (!is_array($data) || !isset($data['fullName'], $data['email'], $data['password'])) {
            throw new HttpException('fullName, email, and password properties must be set');
        }

        if (!is_string($data['fullName']) || !is_string($data['email']) || !is_string($data['password'])) {
            throw new HttpException('Name, email, and password must be strings');
        }

        $fullName = self::validateFullName($data['fullName']);
        self::validateEmail($data['email']);
        self::validatePassword($data['password']);

        $user = $this->getUserByEmail($data['email']);

        if ($user !== null) {
            // todo: don't leak valid emails - instead send email to complete registration and always display same message
            throw new HttpException('An account with this email already exists. Try logging in instead.', StatusCode::CONFLICT);
        }

        $now = (new \DateTime())->format(DbConnector::SQL_DATE);

        $result = $this->db->insertRow('accounts', [
            'fullname' => $fullName,
            'email' => $data['email'],
            'password' => self::hashPassword($data['password']),
            'account_created' => $now,
            'account_last_updated' => $now,
        ]);

        return $result->getId();
    }

    public function updateUserProfile(CurrentUser $user, array|object|null $data): void
    {
        if (!is_array($data) || !isset($data['fullName'])) {
            throw new HttpException('Missing required fullName property');
        }

        if (!is_string($data['fullName'])) {
            throw new HttpException('Name must be a string');
        }

        $fullName = self::validateFullName($data['fullName']);

        $set = [
            'fullname' => $fullName,
            'account_last_updated' => (new \DateTime())->format(DbConnector::SQL_DATE),
        ];

        $this->db->updateRows('accounts', $set, ['account_id' => $user->getId()]);
    }

    public function updateUserEmail(CurrentUser $user, array|object|null $data): void
    {
        if (!is_array($data) || !isset($data['email'])) {
            throw new HttpException('Missing required email property');
        }

        if (!is_string($data['email'])) {
            throw new HttpException('Email must be a string');
        }

        // todo: consider sending a confirmation link to the email to avoid leaking valid emails for other users
        self::validateEmail($data['email']);
        $userWithEmail = $this->getUserByEmail($data['email']);

        if ($userWithEmail !== null && $userWithEmail->getId() !== $user->getId()) {
            throw new HttpException('This email is already in use by a different account.', StatusCode::CONFLICT);
        }

        $set = [
            'email' => $data['email'],
            'account_last_updated' => (new \DateTime())->format(DbConnector::SQL_DATE),
        ];

        $this->db->updateRows('accounts', $set, ['account_id' => $user->getId()]);
    }

    public function updateUserPassword(CurrentUser $user, array|object|null $data): void
    {
        if (!is_array($data) || !isset($data['currentPassword'], $data['newPassword'])) {
            throw new HttpException('currentPassword and newPassword properties are required');
        }

        if (!is_string($data['currentPassword']) || !is_string($data['newPassword'])) {
            throw new HttpException('Current and new password must be strings');
        }

        // todo: rate limit these attempts
        if (!$user->verifyPassword($data['currentPassword'])) {
            throw new HttpException('Current password is invalid');
        }

        self::validatePassword($data['newPassword']);

        $set = [
            'password' => self::hashPassword($data['newPassword']),
            'account_last_updated' => (new \DateTime())->format(DbConnector::SQL_DATE),
        ];

        $this->db->updateRows('accounts', $set, ['account_id' => $user->getId()]);

        (new AuthTokens())->deactivateOtherTokens($user);
    }

    public function getUserByEmail(string $email): ?User
    {
        /** @var UserRow|null $row */
        $row = $this->db->query("SELECT * FROM accounts WHERE email = ?", [$email])->getFirst();

        if ($row === null) {
            return null;
        }

        return new User($row);
    }

    public static function validateFullName(string $fullName): string
    {
        $trimmed = trim($fullName);

        if ($trimmed === '') {
            throw new HttpException('Name cannot be blank');
        }

        return $trimmed;
    }

    public static function validateEmail(string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new HttpException('Invalid email format');
        }
    }

    /**
     * @throws HttpException if password isn't valid
     */
    public static function validatePassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new HttpException('Password must be at least 8 characters in length');
        }
    }

    private static function hashPassword(string $password): string
    {
        $result = password_hash($password, PASSWORD_DEFAULT);
        assert($result !== null);
        return $result;
    }
}
