<?php

declare(strict_types=1);

namespace Phasem;

/**
 * Contains configuration interface/defaults for running the application.
 */
abstract class Config
{
    abstract public function isDevEnv(): bool;

    public function getHost(): string
    {
        return '127.0.0.1';
    }

    public function getDatabase(): string
    {
        return 'phasem';
    }

    abstract public function getUsername(): string;
    abstract public function getPassword(): string;

    /**
     * Key can be generated with vendor/bin/generate-defuse-key
     */
    abstract public function getEncryptionKey(): string;
}
