<?php

declare(strict_types=1);

namespace Phasem\db;

use Phasem\model\User;
use Teapot\{HttpException, StatusCode};

class Users
{
    private $db;

    public function __construct()
    {
        $this->db = DbConnector::getDatabase();
    }

    public function insertUserFromApi(array $data): int
    {
        if (!isset($data['fullName'], $data['email'], $data['password'])) {
            throw new HttpException('Name, email, and password must be set');
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

        $result = $this->db->insertRow('users', [
            'user_fullname' => $fullName,
            'user_email' => $data['email'],
            'user_password' => self::hashPassword($data['password']),
            'user_created' => $now,
            'user_last_updated' => $now,
        ]);

        return $result->getId();
    }

    public function updateUserProfile(User $user, array $data): void
    {
        if (!isset($data['fullName'])) {
            throw new HttpException('Missing required fullName property');
        }

        $fullName = self::validateFullName($data['fullName']);

        $set = [
            'user_fullname' => $fullName,
            'user_last_updated' => (new \DateTime())->format(DbConnector::SQL_DATE),
        ];

        $this->db->updateRows('users', $set, ['user_id' => $user->getId()]);
    }

    public function updateUserEmail(User $user, array $data): void
    {
        if (!isset($data['email'])) {
            throw new HttpException('Missing required email property');
        }

        // todo: consider sending a confirmation link to the email to avoid leaking valid emails for other users
        self::validateEmail($data['email']);
        $userWithEmail = $this->getUserByEmail($data['email']);

        if ($userWithEmail !== null && $userWithEmail->getId() !== $user->getId()) {
            throw new HttpException('This email is already in use by a different account.', StatusCode::CONFLICT);
        }

        $set = [
            'user_email' => $data['email'],
            'user_last_updated' => (new \DateTime())->format(DbConnector::SQL_DATE),
        ];

        $this->db->updateRows('users', $set, ['user_id' => $user->getId()]);
    }

    public function updateUserPassword(User $user, array $data): void
    {
        if (!isset($data['currentPassword'], $data['newPassword'])) {
            throw new HttpException('currentPassword and newPassword properties are required');
        }

        // todo: rate limit these attempts
        if (!$user->verifyPassword($data['currentPassword'])) {
            throw new HttpException('Current password is invalid');
        }

        self::validatePassword($data['newPassword']);

        $set = [
            'user_password' => self::hashPassword($data['newPassword']),
            'user_last_updated' => (new \DateTime())->format(DbConnector::SQL_DATE),
        ];

        $this->db->updateRows('users', $set, ['user_id' => $user->getId()]);

        (new AuthTokens())->deactivateOtherTokens($user);
    }

    public function getUserByEmail(string $email): ?User
    {
        $row = $this->db->query("SELECT * FROM users WHERE user_email = ?", [$email])->getFirst();

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

    public static function validateEmail(string $email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new HttpException('Invalid email format');
        }
    }

    /**
     * @throws HttpException if password isn't valid
     */
    public static function validatePassword(string $password)
    {
        if (strlen($password) < 8) {
            throw new HttpException('Password must be at least 8 characters in length');
        }
    }

    private static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
