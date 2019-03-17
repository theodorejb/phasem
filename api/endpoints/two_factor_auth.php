<?php

use Phasem\db\{AuthTokens, MfaKeys};
use Phasem\model\MfaKey;
use Slim\Http\{Request, Response};
use Teapot\HttpException;

$app->group('/two_factor_auth', function (\Slim\App $app) {
    $app->get('/status', function (Request $request, Response $response) {
        $mfaKeys = new MfaKeys();
        $key = $mfaKeys->getEnabledMfaKey(\Phasem\App::getUser()->getId());

        $data = [
            'isMfaEnabled' => false,
            'backupsLastViewed' => null,
            'unusedBackupCount' => null,
        ];

        if ($key !== null) {
            $data['isMfaEnabled'] = true;
            $data['backupsLastViewed'] = $key->getBackupsLastViewed()->format(DateTime::ATOM);
            $data['unusedBackupCount'] = MfaKey::BACKUP_SET_SIZE - count($mfaKeys->getUsedBackupCounters($key));
        }

        return $response->withJson(['data' => $data]);
    });

    $app->post('/setup', function (Request $request, Response $response) {
        $user = \Phasem\App::getUser();
        $mfaKeys = new MfaKeys();
        $key = $mfaKeys->setupMfa($user->getId());

        return $response->withJson([
            'data' => [
                'backupCodes' => $key->getUnusedBackupCodes([]),
            ],
        ]);
    });

    $app->post('/secret', function (Request $request, Response $response) {
        $user = \Phasem\App::getUser();
        $mfaKeys = new MfaKeys();
        $key = $mfaKeys->getRequestedMfaKey($user->getId());
        $mfaKeys->validateRequestedKey($key, true);

        return $response->withJson([
            'data' => [
                'secret' => $key->getSharedSecret(),
                'qrCode' => $key->makeQrCode($user->getEmail()),
            ],
        ]);
    });

    $app->post('/enable', function (Request $request, Response $response) {
        $code = $request->getParsedBodyParam('code');
        $code = str_replace(' ', '', $code); // remove any spaces from code

        if (!$code) {
            throw new HttpException('Missing required code property');
        }

        $user = \Phasem\App::getUser();
        $mfaKeys = new MfaKeys();
        $key = $mfaKeys->getRequestedMfaKey($user->getId());
        $mfaKeys->validateRequestedKey($key);
        $key->validateTimeBasedCode($code);
        $mfaKeys->enableMfaKey($key);
        $newToken = (new AuthTokens())->updateMfaCompletion($user->getAuthId());

        return $response->withJson(['token' => $newToken]);
    });

    $app->post('/verify', function (Request $request, Response $response) {
        $code = $request->getParsedBodyParam('code');

        if (!$code) {
            throw new HttpException('Missing required code property');
        }

        $user = \Phasem\App::getUser();
        $mfaKeys = new MfaKeys();
        $key = $mfaKeys->getEnabledMfaKey($user->getId());

        if ($key === null) {
            throw new HttpException('Two-factor authentication is not enabled for this account');
        }

        // an enabled key exists - verify the code
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

            if (new DateTimeImmutable() < $key->getLastFailedAttempt()->add($timeToWait)) {
                throw new HttpException('Too many failed attempts. Please wait before trying again.', 429);
            }
        }

        try {
            if ($codeLength === 6) {
                $key->validateTimeBasedCode($code);
            } else {
                // backup code
                $counter = $key->validateBackupCode($code);

                if ($mfaKeys->isBackupCodeUsed($key->getId(), $counter)) {
                    throw new HttpException('Invalid verification code');
                }

                $mfaKeys->useBackupCode($key->getId(), $counter);
            }
        } catch (Exception $e) {
            $mfaKeys->incrementFailedAttempts($key);
            throw $e;
        }

        if ($failedAttempts > 0) {
            $mfaKeys->resetFailedAttempts($key);
        }

        $newToken = (new AuthTokens())->updateMfaCompletion($user->getAuthId());
        return $response->withJson(['token' => $newToken]);
    });

    $app->post('/disable', function (Request $request, Response $response) {
        $mfaKeys = new MfaKeys();
        $key = $mfaKeys->getEnabledMfaKey(\Phasem\App::getUser()->getId());

        if ($key === null) {
            throw new HttpException('Two-factor authentication is already not enabled');
        }

        $mfaKeys->disableMfaKey($key);
        return $response->withStatus(204);
    })->add('recent_mfa_completion');

    $app->group('/backup_codes', function () use ($app) {
        // get current backup codes
        $app->get('', function (Request $request, Response $response) {
            $mfaKeys = new MfaKeys();
            $key = $mfaKeys->getEnabledMfaKey(\Phasem\App::getUser()->getId());

            if ($key === null) {
                throw new HttpException('Two-factor authentication is not enabled');
            }

            $usedCounters = $mfaKeys->getUsedBackupCounters($key);
            $unusedCodes = $key->getUnusedBackupCodes($usedCounters);
            $mfaKeys->updateBackupsLastViewed($key);
            return $response->withJson(['data' => $unusedCodes]);
        });

        // generate a new set of backup codes
        $app->post('', function (Request $request, Response $response) {
            $mfaKeys = new MfaKeys();
            $key = $mfaKeys->getEnabledMfaKey(\Phasem\App::getUser()->getId());

            if ($key === null) {
                throw new HttpException('Two-factor authentication is not enabled');
            }

            $mfaKeys->regenerateBackupCodes($key); // updates key counter
            $unusedCodes = $key->getUnusedBackupCodes([]);
            return $response->withJson(['data' => $unusedCodes]);
        });
    })->add('recent_mfa_completion');
});
