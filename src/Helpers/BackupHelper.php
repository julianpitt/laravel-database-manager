<?php namespace JulianPitt\DBManager\Helpers;

use JulianPitt\DBManager\Classes\MySQLDatabase;
use JulianPitt\DBManager\Console;
use Config;
use Exception;

class BackupHelper
{
    protected $console;
    protected $database;

    public function __construct()
    {
        $this->console = new Console();
    }

    public function getDatabaseConnection(array $realConfig)
    {
        try {
            $this->buildMySQL($realConfig);
        } catch (\Exception $e) {
            throw new \Exception('Whoops, '.$e->getMessage());
        }

        return $this->database;
    }

    protected function buildMySQL(array $config)
    {
        $port = isset($config['port']) ? $config['port'] : 3306;

        $socket = isset($config['unix_socket']) ? $config['unix_socket'] : '';

        $this->database = new MySQLDatabase(
            $this->console,
            $config['database'],
            $config['username'],
            $config['password'],
            $this->determineHost($config),
            $port,
            $socket
        );
    }

    public function determineHost(array $config)
    {
        if (isset($config['host'])) {
            return $config['host'];
        }

        if (isset($config['read']['host'])) {
            return $config['read']['host'];
        }

        throw new \Exception('could not determine host from config');
    }

    public function getDatabase($connectionName = '')
    {
        $connectionName = $connectionName ?: config('database.default');

        $dbDriver = config("database.connections.{$connectionName}.driver");

        if ($dbDriver != 'mysql') {
            throw new Exception('DBBackup currently doesn\'t support your database');
        }

        return $this->getDatabaseConnection(config("database.connections.{$connectionName}"));
    }

    public function getDumpedDatabase()
    {
        $tempFile = tempnam(sys_get_temp_dir(), "dbbackup");

        $success = $this->getDatabase()->dump($tempFile);

        if (! $success || filesize($tempFile) == 0) {
            throw new Exception('Could not create backup of db');
        }

        return $tempFile;
    }

    public function getFilesToBeBackedUp()
    {
        return [$this->getDumpedDatabase()];
    }

    public function getFileExtension()
    {
        return 'sql';
    }


}