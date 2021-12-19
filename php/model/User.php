<?php

declare(strict_types=1);

namespace Phasem\model;

use DateTime;

/**
 * @psalm-type UserRow = array{account_id: int, fullname: string, email: string, password: string, account_created: string, account_last_updated: string}
 */
class User implements \JsonSerializable
{
    private int $id;
    private string $fullName;
    private string $email;
    private string $password;
    private DateTime $dateCreated;
    private DateTime $dateUpdated;

    /**
     * @param UserRow $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['account_id'];
        $this->fullName = $data['fullname'];
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->dateCreated = new DateTime($data['account_created']);
        $this->dateUpdated = new DateTime($data['account_last_updated']);
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

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'fullName' => $this->fullName,
            'email' => $this->email,
            'dateCreated' => $this->dateCreated->format(DateTime::ATOM),
        ];
    }
}
