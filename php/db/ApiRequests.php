<?php

declare(strict_types=1);

namespace Phasem\db;

use DateTimeImmutable;
use PeachySQL\PeachySql;
use PeachySQL\SqlException;
use Phasem\App;
use Phasem\model\CurrentUser;
use Psr\Http\Message\ServerRequestInterface as Request;

class ApiRequests
{
    private PeachySql $db;

    public function __construct()
    {
        $this->db = DbConnector::getDatabase();
    }

    public function recordRequest(CurrentUser $user, Request $request, string $error = null): void
    {
        $now = new DateTimeImmutable();
        $uri = $request->getUri();

        if ($request->getMethod() === 'GET') {
            $params = $request->getQueryParams();
        } elseif ($error !== null) {
            $params = $request->getQueryParams();
            $postParams = $request->getParsedBody();

            if ($postParams) {
                $params = array_merge($params, (array)$postParams);
            }

            $params = App::hashSensitiveKeys($params);
        } else {
            $params = [];
        }

        $paramsJson = (count($params) === 0) ? null : json_encode($params);

        $this->db->insertRow('api_requests', [
            'auth_id' => $user->getAuthId(),
            'method' => $request->getMethod(),
            'endpoint_id' => $this->getEndpointId($uri->getHost(), $uri->getPath()),
            'processing_ended' => $now->format('Y-m-d H:i:s.u'),
            'process_time_ms' => App::getRequestTimeMs(),
            'params' => $paramsJson,
            'error' => $error,
        ]);
    }

    public function getEndpointId(string $host, string $path): int
    {
        $endpointId = $this->getExistingEndpointId($host, $path);

        if ($endpointId !== null) {
            return $endpointId;
        }

        $row = [
            'host' => $host,
            'path' => $path,
        ];

        // A race condition can occur here when there are multiple simultaneous requests to the same endpoint,
        // and both requests will try to insert into the api_endpoints table, causing a duplicate entry error.

        try {
            return $this->db->insertRow('api_endpoints', $row)->getId();
        } catch (SqlException $e) {
            $duplicateMsg = 'Failed to execute prepared statement: Duplicate entry';

            if (substr($e->getMessage(), 0, strlen($duplicateMsg)) === $duplicateMsg) {
                $result = $this->getExistingEndpointId($host, $path);
                assert($result !== null);
                return $result;
            }

            throw $e;
        }
    }

    private function getExistingEndpointId(string $host, string $path): ?int
    {
        /** @var null|array{endpoint_id: int} $existing */
        $existing = $this->db->selectFrom('SELECT endpoint_id FROM api_endpoints')
            ->where(['host' => $host, 'path' => $path])->query()->getFirst();

        return $existing['endpoint_id'] ?? null;
    }
}
