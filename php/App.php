<?php

declare(strict_types=1);

namespace Phasem;

use Defuse\Crypto\{Crypto, Key};
use Phasem\model\User;

class App
{
    private static $config;
    private static $user;
    private static $requestTime;

    public static function setRequestTime(): void
    {
        self::$requestTime = hrtime(true);
    }

    public static function getRequestTimeMs(): int
    {
        if (self::$requestTime === null) {
            throw new \Exception('Request time has not been set');
        }

        $ns = hrtime(true) - self::$requestTime;
        return (int)($ns / 1000000);
    }

    public static function setConfig(array $config): void
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

    public static function setUser(?User $user): void
    {
        self::$user = $user;
    }

    public static function getUser(): ?User
    {
        return self::$user;
    }

    public static function encrypt(string $plaintext, bool $rawBinary = false): string
    {
        $key = Key::loadFromAsciiSafeString(self::getConfig()['encryptionKey']);
        return Crypto::encrypt($plaintext, $key, $rawBinary);
    }

    public static function decrypt(string $ciphertext, bool $rawBinary = false): string
    {
        $key = Key::loadFromAsciiSafeString(self::getConfig()['encryptionKey']);
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
                $data[$property] = hash('sha256', $data[$property]);
            }
        }

        return $data;
    }
}
