<?php

declare(strict_types=1);

namespace Phasem\db;

use DateTimeImmutable;
use Phasem\App;
use Phasem\model\User;
use Slim\Http\Request;

class ApiRequests
{
    private $db;

    public function __construct()
    {
        $this->db = DbConnector::getDatabase();
    }

    public function recordRequest(User $user, Request $request, string $error = null): void
    {
        $now = new DateTimeImmutable();
        $uri = $request->getUri();

        if ($request->getMethod() === 'GET') {
            $params = $request->getQueryParams();
        } elseif ($error !== null) {
            $params = App::hashSensitiveKeys($request->getParams());
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
        $existing = $this->db->selectFrom('SELECT endpoint_id FROM api_endpoints')
            ->where(['host' => $host, 'path' => $path])->query()->getFirst();

        if ($existing !== null) {
            return $existing['endpoint_id'];
        }

        $row = [
            'host' => $host,
            'path' => $path,
        ];

        return $this->db->insertRow('api_endpoints', $row)->getId();
    }
}
