<?php

declare(strict_types=1);

namespace Phasem\db;

use Phasem\model\User;
use Teapot\HttpException;
use Teapot\StatusCode;

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

        self::validateFullName($data['fullName']);
        self::validateEmail($data['email']);
        self::validatePassword($data['password']);

        $user = $this->getUserByEmail($data['email']);

        if ($user !== null) {
            throw new HttpException('An account with this email already exists. Try logging in instead.', StatusCode::CONFLICT);
        }

        $now = (new \DateTime())->format('c');

        $result = $this->db->insertRow('users', [
            'user_fullname' => $data['fullName'],
            'user_email' => $data['email'],
            'user_password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'user_created' => $now,
            'user_last_updated' => $now,
        ]);

        return $result->getId();
    }

    public function getUserByEmail(string $email): ?User
    {
        $row = $this->db->query("SELECT * FROM users WHERE user_email = ?", [$email])->getFirst();

        if ($row === null) {
            return null;
        }

        return new User($row);
    }

    public static function validateFullName(string $fullName)
    {
        if (trim($fullName) === '') {
            throw new HttpException('Name cannot be blank');
        }
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
}
