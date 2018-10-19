<?php

declare(strict_types=1);

namespace Phasem\model;

use DateTimeImmutable;
use ParagonIE\ConstantTime\Base32;
use ParagonIE\MultiFactor\{FIDOU2F, OTP\HOTP};
use ParagonIE\MultiFactor\Vendor\GoogleAuth;
use Phasem\App;
use Teapot\HttpException;

class MfaKey
{
    const BACKUP_SET_SIZE = 8;

    private $id;
    private $userId;
    private $secret; // encrypted
    private $dateRequested;
    private $dateEnabled;
    private $dateDisabled;
    private $failedAttempts;
    private $lastFailedAttempt;
    private $backupCounter;
    private $backupsLastGenerated;
    private $backupsLastViewed;

    private $_seed; // raw bytes

    public function __construct(array $row)
    {
        $this->id = $row['key_id'];
        $this->userId = $row['user_id'];
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

    private function getSeed()
    {
        // only decrypt the secret if it is used
        if (is_null($this->_seed)) {
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
     * Returns a Base64 encoded PNG image
     * Note: this method requires that the PHP GD extension be enabled
     */
    public function makeQrCode(string $email): string
    {
        $auth = new GoogleAuth($this->getSeed());
        $auth->defaultQRCodeWidth = $auth->defaultQRCodeHeight = 250;

        ob_start();
        // see https://github.com/google/google-authenticator/wiki/Key-Uri-Format
        $auth->makeQRCode(null, 'php://output', $email, 'Phasem', 'Phasem');
        return base64_encode(ob_get_clean());
    }

    private function getHOTP(): FIDOU2F
    {
        return new FIDOU2F($this->getSeed(), new HOTP(8));
    }
}
