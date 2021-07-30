<?php

declare(strict_types=1);

namespace Phasem\model;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use DateTimeImmutable;
use ParagonIE\ConstantTime\Base32;
use ParagonIE\MultiFactor\{OneTime, OTP\HOTP};
use ParagonIE\MultiFactor\Vendor\GoogleAuth;
use Phasem\App;
use Teapot\HttpException;

/**
 * @psalm-type MfaKeyRow array{key_id: int, account_id: int, secret: string, mfa_requested: string, mfa_enabled: string|null, mfa_disabled: string|null, failed_attempts: int, last_failed_attempt: string|null, backup_counter: int, backups_last_generated: string, backups_last_viewed: string}
 */
class MfaKey
{
    const BACKUP_SET_SIZE = 8;

    private int $id;
    private int $userId;
    private string $secret; // encrypted
    private DateTimeImmutable $dateRequested;
    private ?DateTimeImmutable $dateEnabled;
    private ?DateTimeImmutable $dateDisabled;
    private int $failedAttempts;
    private ?DateTimeImmutable $lastFailedAttempt;
    private int $backupCounter;
    private DateTimeImmutable $backupsLastGenerated;
    private DateTimeImmutable $backupsLastViewed;

    private ?string $_seed = null; // raw bytes

    /**
     * @param MfaKeyRow $row
     */
    public function __construct(array $row)
    {
        $this->id = $row['key_id'];
        $this->userId = $row['account_id'];
        $this->secret = $row['secret'];
        $this->dateRequested = new DateTimeImmutable($row['mfa_requested']);
        $this->dateEnabled = $row['mfa_enabled'] ? new DateTimeImmutable($row['mfa_enabled']) : null;
        $this->dateDisabled = $row['mfa_disabled'] ? new DateTimeImmutable($row['mfa_disabled']) : null;
        $this->failedAttempts = $row['failed_attempts'];
        $this->lastFailedAttempt = $row['last_failed_attempt'] ? new DateTimeImmutable($row['last_failed_attempt']) : null;
        $this->backupCounter = $row['backup_counter'];
        $this->backupsLastGenerated = new DateTimeImmutable($row['backups_last_generated']);
        $this->backupsLastViewed = new DateTimeImmutable($row['backups_last_viewed']);
    }

    private function getSeed(): string
    {
        // only decrypt the secret if it is used
        if ($this->_seed === null) {
            $this->_seed = App::decrypt($this->secret);
        }

        return $this->_seed;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getDateRequested(): DateTimeImmutable
    {
        return $this->dateRequested;
    }

    public function getDateEnabled(): ?DateTimeImmutable
    {
        return $this->dateEnabled;
    }

    public function getDateDisabled(): ?DateTimeImmutable
    {
        return $this->dateDisabled;
    }

    public function getFailedAttempts(): int
    {
        return $this->failedAttempts;
    }

    public function getLastFailedAttempt(): ?DateTimeImmutable
    {
        return $this->lastFailedAttempt;
    }

    public function getBackupCounter(): int
    {
        return $this->backupCounter;
    }

    public function getNextBackupCounter(): int
    {
        return $this->backupCounter + self::BACKUP_SET_SIZE;
    }

    public function setNextBackupCounter(): void
    {
        $this->backupCounter = $this->getNextBackupCounter();
    }

    public function getBackupsLastGenerated(): DateTimeImmutable
    {
        return $this->backupsLastGenerated;
    }

    public function getBackupsLastViewed(): DateTimeImmutable
    {
        return $this->backupsLastViewed;
    }

    /**
     * @param int[] $usedCounters
     * @return string[]
     */
    public function getUnusedBackupCodes(array $usedCounters): array
    {
        $hotp = $this->getHOTP();
        $codes = [];

        for ($counter = $this->backupCounter; $counter < $this->getNextBackupCounter(); $counter++) {
            if (!in_array($counter, $usedCounters, true)) {
                $codes[] = $hotp->generateCode($counter);
            }
        }

        return $codes;
    }

    /**
     * Returns the counter of the matched backup code
     * @throws HttpException if the backup code is invalid
     */
    public function validateBackupCode(string $code): int
    {
        $standardError = 'Invalid verification code';

        if ($this->dateEnabled === null) {
            throw new HttpException($standardError);
        }

        $hotp = $this->getHOTP();

        for ($counter = $this->backupCounter; $counter < $this->getNextBackupCounter(); $counter++) {
            if ($hotp->validateCode($code, $counter)) {
                return $counter;
            }
        }

        throw new HttpException($standardError);
    }

    /**
     * @throws HttpException if the code is invalid
     */
    public function validateTimeBasedCode(string $code): void
    {
        $auth = new GoogleAuth($this->getSeed());
        $time = time();

        if ($auth->validateCode($code, $time)) {
            return;
        }

        // current code doesn't match - check up to 2 steps back and 2 ahead

        for ($s = 1; $s <= 2; $s++) {
            $diff = $s * 30;

            if ($auth->validateCode($code, $time - $diff) || $auth->validateCode($code, $time + $diff)) {
                return;
            }
        }

        throw new HttpException('Invalid verification code');
    }

    public function getSharedSecret(): string
    {
        return Base32::encode($this->getSeed());
    }

    /**
     * Returns an SVG image
     */
    public function makeQrCode(string $email): string
    {
        $auth = new GoogleAuth($this->getSeed());
        $auth->defaultQRCodeSize = 200;

        $renderer = new ImageRenderer(
            new RendererStyle($auth->defaultQRCodeSize),
            new SvgImageBackEnd(),
        );

        // see https://github.com/google/google-authenticator/wiki/Key-Uri-Format
        return $auth->getQRCode(new Writer($renderer), $email, 'Phasem', 'Phasem');
    }

    private function getHOTP(): OneTime
    {
        return new OneTime($this->getSeed(), new HOTP(8));
    }
}
