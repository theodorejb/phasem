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
    const SQL_DATE = 'Y-m-d H:i:s';

    private static ?mysqli $connection = null;
    private static ?PeachySql $peachySql = null;

    /**
     * Returns a mysqli connection to the app database
     * @throws \Exception if config isn't set or a connection failure occurs
     */
    public static function getConnection(): mysqli
    {
        if (self::$connection === null) {
            $c = App::getConfig();
            self::$connection = new mysqli($c->getHost(), $c->getUsername(), $c->getPassword(), $c->getDatabase());

            if (self::$connection->connect_error) {
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
