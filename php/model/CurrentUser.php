<?php

declare(strict_types=1);

namespace Phasem\model;

use DateTime;

class CurrentUser extends User
{
    private int $authId;
    private ?DateTime $mfaLastCompleted;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->authId = $data['auth_id'];
        $this->mfaLastCompleted = $data['mfa_last_completed'];
    }

    /**
     * Returns the ID of the user's authentication token
     */
    public function getAuthId(): int
    {
        return $this->authId;
    }

    public function getMfaLastCompleted(): ?DateTime
    {
        return $this->mfaLastCompleted;
    }
}
