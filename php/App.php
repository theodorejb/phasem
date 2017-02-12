<?php

declare(strict_types=1);

namespace Phasem;

use Phasem\model\User;

class App
{
    private static $config;
    private static $user;

    public static function setConfig(array $config)
    {
        self::$config = $config;
    }

    /**
     * @throws \Exception if configuration hasn't been set
     */
    public static function getConfig(): array
    {
        if (self::$config === null) {
            throw new \Exception('Config has not been set yet');
        } else {
            return self::$config;
        }
    }

    public static function setUser(?User $user)
    {
        self::$user = $user;
    }

    public static function getUser(): ?User
    {
        return self::$user;
    }
}
