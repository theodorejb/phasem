<?php

declare(strict_types=1);

namespace Phasem;

use Defuse\Crypto\{Crypto, Key};
use Phasem\model\CurrentUser;

class App
{
    private static ?Config $config = null;
    private static ?CurrentUser $user = null;
    private static int $requestTime;

    public static function setRequestTime(): void
    {
        self::$requestTime = hrtime(true);
    }

    public static function getRequestTimeMs(): int
    {
        $ns = hrtime(true) - self::$requestTime;
        return (int)($ns / 1000000);
    }

    public static function setConfig(Config $config): void
    {
        self::$config = $config;
    }

    public static function getConfig(): Config
    {
        if (self::$config === null) {
            throw new \Exception('App configuration has not been set');
        }

        return self::$config;
    }

    public static function setUser(?CurrentUser $user): void
    {
        self::$user = $user;
    }

    public static function getUser(): CurrentUser
    {
        if (self::$user === null) {
            throw new \Exception('Current user has not been set');
        }

        return self::$user;
    }

    public static function getUserOrNull(): ?CurrentUser
    {
        return self::$user;
    }

    public static function encrypt(string $plaintext, bool $rawBinary = false): string
    {
        $key = Key::loadFromAsciiSafeString(self::getConfig()->getEncryptionKey());
        return Crypto::encrypt($plaintext, $key, $rawBinary);
    }

    public static function decrypt(string $ciphertext, bool $rawBinary = false): string
    {
        $key = Key::loadFromAsciiSafeString(self::getConfig()->getEncryptionKey());
        return Crypto::decrypt($ciphertext, $key, $rawBinary);
    }

    public static function hashSensitiveKeys(array $data): array
    {
        $properties = [
            'password',
            'newPassword',
            'currentPassword',
        ];

        foreach ($properties as $property) {
            if (isset($data[$property]) && $data[$property] !== '') {
                $data[$property] = hash('sha256', (string) $data[$property]);
            }
        }

        return $data;
    }
}
