<?php

declare(strict_types=1);

namespace Phasem\db;

use PeachySQL\PeachySql;

class UserAgents
{
    private PeachySql $db;

    public function __construct()
    {
        $this->db = DbConnector::getDatabase();
    }

    public function getUserAgentId(string $userAgent): int
    {
        $userAgent = self::trimUserAgent($userAgent);
        /** @var null|array{user_agent_id: int} $existing */
        $existing = $this->db->selectFrom('SELECT user_agent_id FROM user_agents')
            ->where(['user_agent' => $userAgent])->query()->getFirst();

        if ($existing !== null) {
            return $existing['user_agent_id'];
        }

        return $this->addUserAgent($userAgent);
    }

    private function addUserAgent(string $userAgent): int
    {
        $row = [
            'user_agent' => $userAgent,
            'user_agent_created' => date(DbConnector::SQL_DATE),
        ];

        return $this->db->insertRow('user_agents', $row)->getId();
    }

    public static function trimUserAgent(string $userAgent): string
    {
        return mb_substr($userAgent, 0, 768);
    }
}
