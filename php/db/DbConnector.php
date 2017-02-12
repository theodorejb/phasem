<?php

declare(strict_types=1);

namespace Phasem\db;

use mysqli;
use PeachySQL\{Mysql, PeachySql};
use Phasem\App;

/**
 * Responsible for creating and retrieving a database connection
 */
class DbConnector
{
    private static $connection;
    private static $peachySql;

    /**
     * Returns a mysqli connection to the app database
     * @throws \Exception if config isn't set or a connection failure occurs
     */
    public static function getConnection(): mysqli
    {
        if (self::$connection === null) {
            $db = App::getConfig()['db'];

            self::$connection = new mysqli($db['host'], $db['username'], $db['password'], $db['database']);

            if (self::$connection->connect_errno !== 0) {
                throw new \Exception('Failed to connect to MySQL: ' . self::$connection->connect_error);
            }
        }

        return self::$connection;
    }

    public static function getDatabase(): PeachySql
    {
        if (self::$peachySql === null) {
            self::$peachySql = new Mysql(self::getConnection());
        }

        return self::$peachySql;
    }
}
