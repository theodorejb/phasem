<?php

declare(strict_types=1);

namespace Phasem\routes;

use Phasem\App;
use Phasem\db\{AuthTokens, MfaKeys};
use Phasem\middleware\{AllRequests, RecentMfaCompletion};
use Phasem\model\MfaKey;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use Teapot\HttpException;

class TwoFactorAuth
{
    public function __invoke(RouteCollectorProxy $app)
    {
        $app->get('/status', function (Request $_req, Response $response) {
            $mfaKeys = new MfaKeys();
            $key = $mfaKeys->getEnabledMfaKey(App::getUser()->getId());

            $data = [
                'isMfaEnabled' => false,
                'backupsLastViewed' => null,
                'unusedBackupCount' => null,
            ];

            if ($key !== null) {
                $data['isMfaEnabled'] = true;
                $data['backupsLastViewed'] = $key->getBackupsLastViewed()->format(\DateTime::ATOM);
                $data['unusedBackupCount'] = MfaKey::BACKUP_SET_SIZE - count($mfaKeys->getUsedBackupCounters($key));
            }

            return AllRequests::jsonResp($response, ['data' => $data]);
        });

        $app->post('/setup', function (Request $_req, Response $response) {
            $user = App::getUser();
            $mfaKeys = new MfaKeys();
            $key = $mfaKeys->setupMfa($user->getId());

            return AllRequests::jsonResp($response, [
                'data' => [
                    'backupCodes' => $key->getUnusedBackupCodes([]),
                ],
            ]);
        });

        $app->post('/secret', function (Request $_req, Response $response) {
            $user = App::getUser();
            $mfaKeys = new MfaKeys();
            $key = $mfaKeys->getRequestedMfaKey($user->getId());
            $mfaKeys->validateRequestedKey($key, true);
            assert($key !== null);
            $secret = strtoupper($key->getSharedSecret());

            return AllRequests::jsonResp($response, [
                'data' => [
                    'secret' => implode(' ', str_split($secret, 4)),
                    'qrCode' => $key->makeQrCode($user->getEmail()),
                ],
            ]);
        });

        $app->post('/enable', function (Request $request, Response $response) {
            $body = $request->getParsedBody();
            $code = MfaKeys::getCodeFromBody($body);
            $user = App::getUser();
            $mfaKeys = new MfaKeys();
            $key = $mfaKeys->getRequestedMfaKey($user->getId());
            $mfaKeys->validateRequestedKey($key);
            assert($key !== null);
            $key->validateTimeBasedCode($code);
            $mfaKeys->enableMfaKey($key);
            $newToken = (new AuthTokens())->updateMfaCompletion($user->getAuthId());

            return AllRequests::jsonResp($response, ['token' => $newToken]);
        });

        $app->post('/verify', function (Request $request, Response $response) {
            $body = $request->getParsedBody();
            $code = MfaKeys::getCodeFromBody($body);
            $user = App::getUser();
            $mfaKeys = new MfaKeys();
            $key = $mfaKeys->getEnabledMfaKey($user->getId());

            if ($key === null) {
                throw new HttpException('Two-factor authentication is not enabled for this account');
            }

            // an enabled key exists - verify the code
            $mfaKeys->validateCode($key, $code);
            $newToken = (new AuthTokens())->updateMfaCompletion($user->getAuthId());
            return AllRequests::jsonResp($response, ['token' => $newToken]);
        });

        $app->post('/disable', function (Request $_req, Response $response) {
            $mfaKeys = new MfaKeys();
            $key = $mfaKeys->getEnabledMfaKey(App::getUser()->getId());

            if ($key === null) {
                throw new HttpException('Two-factor authentication is already not enabled');
            }

            $mfaKeys->disableMfaKey($key);
            return $response->withStatus(204);
        })->add(RecentMfaCompletion::class);

        $app->group('/backup_codes', function (RouteCollectorProxy $app) {
            // get current backup codes
            $app->get('', function (Request $_req, Response $response) {
                $mfaKeys = new MfaKeys();
                $key = $mfaKeys->getEnabledMfaKey(App::getUser()->getId());

                if ($key === null) {
                    throw new HttpException('Two-factor authentication is not enabled');
                }

                $usedCounters = $mfaKeys->getUsedBackupCounters($key);
                $unusedCodes = $key->getUnusedBackupCodes($usedCounters);
                $mfaKeys->updateBackupsLastViewed($key);
                return AllRequests::jsonResp($response, ['data' => $unusedCodes]);
            });

            // generate a new set of backup codes
            $app->post('', function (Request $_req, Response $response) {
                $mfaKeys = new MfaKeys();
                $key = $mfaKeys->getEnabledMfaKey(App::getUser()->getId());

                if ($key === null) {
                    throw new HttpException('Two-factor authentication is not enabled');
                }

                $mfaKeys->regenerateBackupCodes($key); // updates key counter
                $unusedCodes = $key->getUnusedBackupCodes([]);
                return AllRequests::jsonResp($response, ['data' => $unusedCodes]);
            });
        })->add(RecentMfaCompletion::class);
    }
}
