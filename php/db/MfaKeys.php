<?php

declare(strict_types=1);

namespace Phasem\db;

use DateInterval, DateTimeImmutable, Exception;
use PeachySQL\PeachySql;
use Phasem\App;
use Phasem\model\MfaKey;
use Teapot\HttpException;

/**
 * @psalm-import-type MfaKeyRow from \Phasem\model\MfaKey
 */
class MfaKeys
{
    private PeachySql $db;

    public function __construct()
    {
        $this->db = DbConnector::getDatabase();
    }

    public function createMfaKey(int $userId): MfaKey
    {
        $now = date(DbConnector::SQL_DATE);

        $row = [
            'account_id' => $userId,
            'secret' => App::encrypt(random_bytes(10)),
            'mfa_requested' => $now,
            'mfa_enabled' => null,
            'mfa_disabled' => null,
            'failed_attempts' => 0,
            'last_failed_attempt' => null,
            'backup_counter' => 0,
            'backups_last_generated' => $now,
            'backups_last_viewed' => $now,
        ];

        $row['key_id'] = $this->db->insertRow('mfa_keys', $row)->getId();
        return new MfaKey($row);
    }

    public function enableMfaKey(MfaKey $key): void
    {
        $this->db->begin();

        // disable any current enabled keys
        $set = ['mfa_disabled' => date(DbConnector::SQL_DATE)];

        $this->db->updateRows('mfa_keys', $set, [
            'account_id' => $key->getUserId(),
            'mfa_enabled' => ['nn' => ''],
            'mfa_disabled' => ['nu' => ''],
        ]);

        // enable the specified key
        $set = ['mfa_enabled' => date(DbConnector::SQL_DATE)];
        $this->db->updateRows('mfa_keys', $set, ['key_id' => $key->getId()]);

        $this->removeNeverEnabledKeys($key->getUserId());
        $this->db->commit();
    }

    public function removeNeverEnabledKeys(int $userId): void
    {
        $this->db->deleteFrom('mfa_keys', [
            'account_id' => $userId,
            'mfa_enabled' => ['nu' => ''],
            'mfa_disabled' => ['nu' => ''],
        ]);
    }

    public function disableMfaKey(MfaKey $key): void
    {
        $set = ['mfa_disabled' => date(DbConnector::SQL_DATE)];
        $this->db->updateRows('mfa_keys', $set, ['key_id' => $key->getId()]);
    }

    public function incrementFailedAttempts(MfaKey $key): void
    {
        $set = [
            'failed_attempts' => $key->getFailedAttempts() + 1,
            'last_failed_attempt' => date(DbConnector::SQL_DATE),
        ];

        $this->db->updateRows('mfa_keys', $set, ['key_id' => $key->getId()]);
    }

    public function resetFailedAttempts(MfaKey $key): void
    {
        $set = ['failed_attempts' => 0];
        $this->db->updateRows('mfa_keys', $set, ['key_id' => $key->getId()]);
    }

    /**
     * Returns the user's current MFA info or null if they don't have MFA enabled
     */
    public function getEnabledMfaKey(int $userId): ?MfaKey
    {
        $sql = "SELECT * FROM mfa_keys
                WHERE account_id = ?
                AND mfa_enabled IS NOT NULL
                AND mfa_disabled IS NULL";

        /** @var MfaKeyRow|null $row */
        $row = $this->db->query($sql, [$userId])->getFirst();
        return is_null($row) ? null : new MfaKey($row);
    }

    /**
     * Returns the requested but not enabled MFA key for the user, if one exists
     */
    public function getRequestedMfaKey(int $userId): ?MfaKey
    {
        $sql = "SELECT * FROM mfa_keys
                WHERE account_id = ?
                AND mfa_enabled IS NULL
                AND mfa_disabled IS NULL";

        /** @var MfaKeyRow|null $row */
        $row = $this->db->query($sql, [$userId])->getFirst();
        return is_null($row) ? null : new MfaKey($row);
    }

    public function setupMfa(int $userId): MfaKey
    {
        $this->db->begin();
        $key = $this->getRequestedMfaKey($userId);

        if ($key === null) {
            $key = $this->createMfaKey($userId);
        } elseif ($key->getDateRequested() < new DateTimeImmutable('20 minutes ago')) {
            $this->removeNeverEnabledKeys($userId);
            $key = $this->createMfaKey($userId);
        }

        $this->db->commit();
        return $key;
    }

    public function validateRequestedKey(?MfaKey $key, bool $renew = false): void
    {
        // clients should redirect to authentication page when this error occurs
        $error = 'No two-factor setup found. Please attempt setup again.';

        if ($key === null) {
            throw new HttpException($error);
        }

        // requested keys should be valid for a period of time, but not indefinitely (to allow reloading state)

        if ($key->getDateRequested() < new DateTimeImmutable('20 minutes ago')) {
            $this->removeNeverEnabledKeys($key->getUserId());
            throw new HttpException($error);
        } elseif ($renew && $key->getDateRequested() < new DateTimeImmutable('10 minutes ago')) {
            $set = ['mfa_requested' => date(DbConnector::SQL_DATE)];
            $this->db->updateRows('mfa_keys', $set, ['key_id' => $key->getId()]);
        }
    }

    /**
     * @throws HttpException if the code is invalid
     */
    public function validateCode(MfaKey $key, string $code): void
    {
        $code = str_replace(' ', '', $code); // remove any spaces from code
        $codeLength = strlen($code);

        if ($codeLength !== 6 && $codeLength !== 8) {
            // invalid code length
            throw new HttpException('Invalid verification code');
        }

        // if 5 or more previous attempts failed, require user to wait before trying again
        // todo: change to count separate set of attempts for users with device ID who have already confirmed 2FA for this secret
        $failedAttempts = $key->getFailedAttempts();

        if ($failedAttempts >= 5) {
            $timeToWait = DateInterval::createFromDateString(pow($failedAttempts - 4, 2) . ' seconds');
            $lastFailed = $key->getLastFailedAttempt();
            assert($lastFailed !== null);

            if (new DateTimeImmutable() < $lastFailed->add($timeToWait)) {
                throw new HttpException('Too many failed attempts. Please wait before trying again.', 429);
            }
        }

        try {
            if ($codeLength === 6) {
                $key->validateTimeBasedCode($code);
            } else {
                // backup code
                $counter = $key->validateBackupCode($code);

                if ($this->isBackupCodeUsed($key->getId(), $counter)) {
                    throw new HttpException('Invalid verification code');
                }

                $this->useBackupCode($key->getId(), $counter);
            }
        } catch (Exception $e) {
            $this->incrementFailedAttempts($key);
            throw $e;
        }

        if ($failedAttempts > 0) {
            $this->resetFailedAttempts($key);
        }
    }

    public function regenerateBackupCodes(MfaKey $key): void
    {
        $key->setNextBackupCounter();
        $now = date(DbConnector::SQL_DATE);

        $set = [
            'backups_last_viewed' => $now,
            'backups_last_generated' => $now,
            'backup_counter' => $key->getBackupCounter(),
        ];

        $this->db->updateRows('mfa_keys', $set, ['key_id' => $key->getId()]);
    }

    public function updateBackupsLastViewed(MfaKey $key): void
    {
        $set = [
            'backups_last_viewed' => date(DbConnector::SQL_DATE),
        ];

        $this->db->updateRows('mfa_keys', $set, ['key_id' => $key->getId()]);
    }

    /**
     * @return int[]
     */
    public function getUsedBackupCounters(MfaKey $key): array
    {
        $sql = "SELECT counter
                FROM mfa_used_backup_codes
                WHERE key_id = ?
                AND counter >= ?";

        $rows = $this->db->query($sql, [$key->getId(), $key->getBackupCounter()])->getAll();
        return array_map(fn(array $r): int => $r['counter'], $rows);
    }

    public function isBackupCodeUsed(int $keyId, int $counter): bool
    {
        $sql = "SELECT counter
                FROM mfa_used_backup_codes
                WHERE key_id = ?
                AND counter = ?";

        $row = $this->db->query($sql, [$keyId, $counter])->getFirst();
        return !is_null($row);
    }

    public function useBackupCode(int $keyId, int $counter): void
    {
        $this->db->insertRow('mfa_used_backup_codes', [
            'key_id' => $keyId,
            'counter' => $counter,
            'date_used' => date(DbConnector::SQL_DATE),
        ]);
    }

    public static function getCodeFromBody(array|null|object $body): string
    {
        if (!is_array($body) || !isset($body['code'])) {
            throw new HttpException('Missing required code property');
        }

        $code = $body['code'];

        if (gettype($code) !== 'string') {
            throw new HttpException('code property must be a string');
        }

        $code = str_replace(' ', '', $code); // remove any spaces from code

        if ($code === '') {
            throw new HttpException('code property cannot be blank');
        }

        return $code;
    }
}
