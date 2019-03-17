<?php

declare(strict_types=1);

namespace Phasem\model;

use DateTime;

class User implements \JsonSerializable
{
    private $id;
    private $fullName;
    private $email;
    private $password;
    private $dateCreated;
    private $dateUpdated;

    public function __construct(array $data)
    {
        $this->id = $data['user_id'];
        $this->fullName = $data['user_fullname'];
        $this->email = $data['user_email'];
        $this->password = $data['user_password'];
        $this->dateCreated = new DateTime($data['user_created']);
        $this->dateUpdated = new DateTime($data['user_last_updated']);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'fullName' => $this->fullName,
            'email' => $this->email,
            'dateCreated' => $this->dateCreated->format(DateTime::ATOM),
        ];
    }
}
