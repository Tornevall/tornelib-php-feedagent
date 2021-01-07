<?php

namespace TorneLIB\Model\Interfaces;

use TorneLIB\Model\Database\Types;
use TorneLIB\Module\Config\DatabaseConfig;

/**
 * Interface DatabaseInterface
 * @package TorneLIB\Module\Interfaces
 * @since 6.1.0
 */
interface DatabaseInterface
{
    /**
     * DatabaseInterface constructor.
     *
     * For internal driver, 6.0-compatible content in constructor is set in this order:
     * serverIdentifier (string)
     * serverOptions = (array)
     * serverHostAddr = (string)
     * serverUsername = (string)
     * serverPassword = (string)
     * @since 6.1.0
     */
    public function __construct();

    /**
     * @return DatabaseConfig
     * @since 6.1.0
     */
    public function getConfig();

    /**
     * @param DatabaseConfig $databaseConfig
     * @return mixed
     * @since 6.1.0
     */
    public function setConfig($databaseConfig);

    /**
     * @param null $identifierName
     * @return int
     * @since 6.1.0
     */
    public function getLastInsertId($identifierName = null);

    /**
     * Connector. If no parameters are set, client will try defaults.
     * @param string $serverIdentifier
     * @param array $serverOptions
     * @param string $serverHostAddr
     * @param string $serverUsername
     * @param string $serverPassword
     * @return mixed
     * @since 6.1.0
     */
    public function connect(
        $serverIdentifier = 'default',
        $serverOptions = [],
        $serverHostAddr = '127.0.0.1',
        $serverUsername = 'username',
        $serverPassword = 'password'
    );

    /**
     * Prepare to enter schema/database. Prior name db()
     * @param $schemaName
     * @param null $identifierName
     * @return mixed
     * @since 6.1.0
     */
    public function setDatabase($schemaName, $identifierName = null);

    /**
     * @param $identifierName
     * @param bool $throwable
     * @return string
     * @since 6.1.0
     */
    public function getDatabase($identifierName, $throwable = false);

    /**
     * @param string $identifierName
     * @return mixed
     * @since 6.1.0
     */
    public function setIdentifier($identifierName);

    /**
     * @return string
     * @since 6.1.0
     */
    public function getIdentifier();

    /**
     * @param int $portNumber
     * @param null $identifierName
     * @return mixed
     * @since 6.1.0
     */
    public function setServerPort($portNumber, $identifierName = null);

    /**
     * @param null $identifierName
     * @return int
     * @since 6.1.0
     */
    public function getServerPort($identifierName = null);

    /**
     * @param string $serverHost
     * @param null $identifierName
     * @return mixed
     * @since 6.1.0
     */
    public function setServerHost($serverHost, $identifierName = null);

    /**
     * @param null $identifierName
     * @return string
     * @since 6.1.0
     */
    public function getServerHost($identifierName = null);

    /**
     * @param $userName
     * @param null $identifierName
     * @return mixed
     * @since 6.1.0
     */
    public function setServerUser($userName, $identifierName = null);

    /**
     * @param null $identifierName
     * @return string
     * @since 6.1.0
     */
    public function getServerUser($identifierName = null);

    /**
     * @param $password
     * @param null $identifierName
     * @return mixed
     * @since 6.1.0
     */
    public function setServerPassword($password, $identifierName = null);

    /**
     * @param null $identifierName
     * @return string
     * @since 6.1.0
     */
    public function getServerPassword($identifierName = null);

    /**
     * @param int $databaseType
     * @param null $identifierName
     * @return mixed
     * @since 6.1.0
     */
    public function setServerType($databaseType = Types::MYSQL, $identifierName = null);

    /**
     * @param null $identifierName
     * @return Types
     * @since 6.1.0
     */
    public function getServerType($identifierName = null);

    /**
     * @param $serverOptions
     * @param null $identifierName
     * @return mixed
     * @since 6.1.0
     */
    public function setServerOptions($serverOptions, $identifierName = null);

    /**
     * @param null $identifierName
     * @return mixed
     * @since 6.1.0
     */
    public function getServerOptions($identifierName = null);

    /**
     * setQuery (query).
     * @param string $queryString
     * @param array $parameters
     * @param null $identifierName
     * @return mixed
     * @since 6.1.0
     */
    public function setQuery($queryString, $parameters = [], $identifierName = null);

    /**
     * getFirst (prior: query_first).
     * @param string $queryString
     * @param array $parameters
     * @param null $identifierName
     * @param bool $assoc
     * @return mixed
     * @since 6.1.0
     */
    public function getFirst($queryString, $parameters = [], $identifierName = null, $assoc = true);

    /**
     * getRow (prior: fetch first row)
     * @param $resource
     * @param null $identifierName
     * @param bool $assoc
     * @return mixed
     * @since 6.1.0
     */
    public function getRow($resource = null, $identifierName = null, $assoc = true);
}
