<?php

namespace TorneLIB\Module\Config;

use JsonMapper;
use JsonMapper_Exception;
use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Model\Database\Configuration;
use TorneLIB\Model\Database\Drivers;
use TorneLIB\Model\Database\Ports;
use TorneLIB\Model\Database\Servers;
use TorneLIB\Model\Database\Types;
use TorneLIB\Module\Network;
use TorneLIB\Utils\Security;

/**
 * Class DatabaseConfig
 * @package TorneLIB\Module
 * @since 6.1.0
 */
class DatabaseConfig
{
    /**
     * @var array $database Schema names.
     * @since 6.1.0
     */
    private $database = [];

    /**
     * @var string $identifier Current identifier. If none, this is always the localhost.
     * @since 6.1.0
     */
    private $identifier = 'default';

    /**
     * @var array $identifiers Collection of added identifiers.
     * @since 6.1.0
     */
    private $identifiers = [];

    /**
     * Always default to MySQL
     * @var array $serverPort
     * @since 6.1.0
     */
    private $serverPort = [
        'default' => Ports::MYSQL,
    ];

    /**
     * @var array $serverHost
     * @since 6.1.0
     */
    private $serverHost = [];

    /**
     * @var array $serverUser
     * @since 6.1.0
     */
    private $serverUser = [];

    /**
     * @var array
     * @since 6.1.0
     */
    private $serverPassword = [];

    /**
     * @var array
     * @since 6.1.0
     */
    private $serverType = [
        'default' => Types::MYSQL,
    ];

    /**
     * @var array
     * @since 6.1.0
     */
    private $serverOptions;

    /**
     * @var array Collection of established connection.
     * @since 6.1.0
     */
    private $connection = [];

    /**
     * @var array $queryResult
     */
    private $queryResult = [];

    /**
     * @var int $defaultTimeout Default connect timeout if any.
     * @since 6.1.0
     */
    private $defaultTimeout = 10;

    /**
     * @var array $timeout Server timeouts.
     * @since 6.1.0
     */
    private $timeout = [];

    /**
     * @var int $preferredDriver Preferred database driver.
     * @since 6.1.0
     */
    private $preferredDriver = [
        'default' => null,
    ];

    /**
     * @var array $lastInsertId
     * @since 6.1.0
     */
    private $lastInsertId = [
        'default' => null,
    ];

    /**
     * @var array $affectedRows
     * @since 6.1.0
     */
    private $affectedRows = [
        'default' => null,
    ];

    /**
     * @var array $statements
     * @since 6.1.0
     */
    private $statements = [
        'default' => null,
    ];

    /**
     * DatabaseConfig constructor.
     * @todo 6.0-compat.
     * @since 6.1.0
     */
    public function __construct()
    {
        $this->serverOptions = [];
    }

    /**
     * @return int
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public static function getDefaultDriver()
    {
        $return = Drivers::DRIVER_OR_METHOD_UNAVAILABLE;

        if (Security::getCurrentFunctionState('mysqli_connect', false)) {
            $return = Drivers::MYSQL_IMPROVED;
        } elseif (Security::getCurrentClassState('PDO', false) && DatabaseConfig::getCanPdo()) {
            $return = Drivers::MYSQL_PDO;
        } elseif (Security::getCurrentFunctionState('mysql_connect', false)) {
            $return = Drivers::MYSQL_DEPRECATED;
        }

        return $return;
    }

    /**
     * @return bool
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public static function getCanPdo()
    {
        $return = false;
        if (Security::getCurrentClassState('PDO', false)) {
            $pdoDriversStatic = \PDO::getAvailableDrivers();
            if (in_array('mysql', $pdoDriversStatic, true)) {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Get name of chosen database for connection ("use schema").
     *
     * @param string $identifier
     * @param bool $throwable
     * @return string
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getDatabase($identifier = null, $throwable = true)
    {
        $currentIdentifier = $this->getCurrentIdentifier($identifier);
        $return = isset($this->database[$currentIdentifier]) ?
            $this->database[$currentIdentifier] : null;

        // Make sure the variable exists before using ikt.
        if ($throwable && is_null($return)) {
            throw new ExceptionHandler(
                sprintf(
                    'Database is not set for connection "%s".',
                    !empty($identifier) ? $identifier : $this->identifier
                ),
                Constants::LIB_DATABASE_NOT_SET
            );
        }

        return (string)$return;
    }

    /**
     * @param string $database
     * @param string $identifierName
     * @return DatabaseConfig
     * @since 6.1.0
     */
    public function setDatabase($database, $identifierName = null)
    {
        $this->database[$this->getCurrentIdentifier($identifierName)] = $database;

        return $this;
    }

    /**
     * @param null $identifier
     * @return string
     * @since 6.1.0
     */
    public function getCurrentIdentifier($identifier = null)
    {
        $return = $this->getIdentifier();
        if (!empty($identifier) && !is_null($identifier)) {
            $return = $identifier;
        }
        return $return;
    }

    /**
     * Returns current identifier even if there may be more identifiers added.
     * @return string
     * @since 6.1.0
     */
    public function getIdentifier()
    {
        return !empty($this->identifier) ? $this->identifier : 'default';
    }

    /**
     * Set "current" identifier.
     * @param string $identifier
     * @return DatabaseConfig
     * @since 6.1.0
     */
    public function setIdentifier($identifier)
    {
        if (!in_array($identifier, $this->identifiers, true)) {
            $this->identifiers[] = $identifier;
        }

        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @param $result
     * @param $identifierName
     * @return $this
     * @since 6.1.0
     */
    public function setResult($result, $identifierName = null)
    {
        $this->queryResult[$this->getCurrentIdentifier($identifierName)] = $result;

        return $this;
    }

    /**
     * @param null $identifierName
     * @return mixed|null
     * @since 6.1.0
     */
    public function getResult($identifierName = null)
    {
        $currentIdentifier = $this->getCurrentIdentifier($identifierName);

        return isset($this->queryResult[$currentIdentifier]) ?
            $this->queryResult[$currentIdentifier] : null;
    }

    /**
     * @return array
     * @since 6.1.0
     */
    public function getIdentifiers()
    {
        return $this->identifiers;
    }

    /**
     * @param null $identifier
     * @return null|string
     * @since 6.1.0
     */
    public function getServerPort($identifier = null)
    {
        $currentIdentifier = $this->getCurrentIdentifier($identifier);
        return isset($this->serverPort[$currentIdentifier]) ?
            $this->serverPort[$currentIdentifier] : null;
    }

    /**
     * @param int $serverPort
     * @param null $identifier
     * @return DatabaseConfig
     * @since 6.1.0
     */
    public function setServerPort($serverPort, $identifier = null)
    {
        $this->serverPort[$this->getCurrentIdentifier($identifier)] = $serverPort;

        return $this;
    }

    /**
     * @param null $identifier
     * @return string
     * @since 6.1.0
     */
    public function getServerHost($identifier = null)
    {
        $currentIdentifier = $this->getCurrentIdentifier($identifier);
        return isset($this->serverHost[$currentIdentifier]) ?
            $this->serverHost[$currentIdentifier] : '127.0.0.1';
    }

    /**
     * @param string $serverHost
     * @param null $identifier
     * @return DatabaseConfig
     * @since 6.1.0
     */
    public function setServerHost($serverHost, $identifier = null)
    {
        $useServerHost = $serverHost;
        if (filter_var($serverHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $useServerHost = sprintf('[%s]', $serverHost);
        }
        $this->serverHost[$this->getCurrentIdentifier($identifier)] = $useServerHost;

        return $this;
    }

    /**
     * @param null $identifier
     * @return null|string
     * @since 6.1.0
     */
    public function getServerUser($identifier = null)
    {
        $currentIdentifier = $this->getCurrentIdentifier($identifier);
        return isset($this->serverUser[$currentIdentifier]) ?
            $this->serverUser[$currentIdentifier] : null;
    }

    /**
     * @param array $serverUser
     * @param null $identifier
     * @return DatabaseConfig
     * @since 6.1.0
     */
    public function setServerUser($serverUser, $identifier = null)
    {
        $this->serverUser[$this->getCurrentIdentifier($identifier)] = $serverUser;

        return $this;
    }

    /**
     * @param null $identifier
     * @return null|string
     * @since 6.1.0
     */
    public function getServerPassword($identifier = null)
    {
        $currentIdentifier = $this->getCurrentIdentifier($identifier);
        return isset($this->serverPassword[$currentIdentifier]) ?
            $this->serverPassword[$currentIdentifier] : null;
    }

    /**
     * @param $serverPassword
     * @param null $identifier
     * @return DatabaseConfig
     * @since 6.1.0
     */
    public function setServerPassword($serverPassword, $identifier = null)
    {
        $this->serverPassword[$this->getCurrentIdentifier($identifier)] = $serverPassword;

        return $this;
    }

    /**
     * @param null $identifier
     * @return Types
     * @since 6.1.0
     */
    public function getServerType($identifier = null)
    {
        $currentIdentifier = $this->getCurrentIdentifier($identifier);
        return isset($this->serverType[$currentIdentifier]) ?
            $this->serverType[$currentIdentifier] : null;
    }

    /**
     * @param int $serverType
     * @param null $identifier
     * @return DatabaseConfig
     * @since 6.1.0
     */
    public function setServerType($serverType = Types::MYSQL, $identifier = null)
    {
        $this->serverType[$this->getCurrentIdentifier($identifier)] = $serverType;

        return $this;
    }

    /**
     * @param null $identifier
     * @return array
     * @since 6.1.0
     */
    public function getServerOptions($identifier = null)
    {
        $currentIdentifier = $this->getCurrentIdentifier($identifier);
        return isset($this->serverOptions[$currentIdentifier]) ?
            $this->serverOptions[$currentIdentifier] : [];
    }

    /**
     * @param $serverOptions
     * @param null $identifier
     * @return DatabaseConfig
     * @since 6.1.0
     */
    public function setServerOptions($serverOptions, $identifier = null)
    {
        $currentIdentifier = $this->getCurrentIdentifier($identifier);
        if (is_array($serverOptions)) {
            if (!isset($this->serverOptions[$currentIdentifier])) {
                $this->serverOptions[$currentIdentifier] = [];
            }
            foreach ($serverOptions as $key => $value) {
                $this->serverOptions[$currentIdentifier][$key] = $value;
            }
        } else {
            return $this->setServerOptions([], $identifier);
        }

        return $this;
    }

    /**
     * @param $jsonFile
     * @return mixed
     * @throws ExceptionHandler
     * @throws JsonMapper_Exception
     * @since 6.1.0
     */
    public function getConfig($jsonFile)
    {
        $return = null;

        if (file_exists($jsonFile)) {
            $return = $this->getConfigByJson($jsonFile);
        } else {
            throw new ExceptionHandler(
                sprintf(
                    'Configuration file %s not found.',
                    $jsonFile
                ),
                404
            );
        }

        return $return;
    }

    /**
     * @param $jsonFile
     * @return Servers
     * @throws ExceptionHandler
     * @throws JsonMapper_Exception
     * @since 6.1.0
     */
    private function getConfigByJson($jsonFile)
    {
        /** @noinspection PhpComposerExtensionStubsInspection */
        $map = @json_decode(
            @file_get_contents($jsonFile),
            false
        );
        if (is_null($map)) {
            $this->throwNoConfig(__CLASS__, __FUNCTION__, $jsonFile);
        }

        return $this->getMappedJson($map);
    }

    /**
     * @param $class
     * @param $function
     * @param $jsonFile
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function throwNoConfig($class, $function, $jsonFile)
    {
        throw new ExceptionHandler(
            sprintf(
                'Function %s::%s called by file %s did not contain any configuration.',
                $class,
                $function,
                $jsonFile
            ),
            Constants::LIB_DATABASE_EMPTY_JSON_CONFIG
        );
    }

    /**
     * @param $mapFromJson
     * @return Servers
     * @throws ExceptionHandler
     * @throws JsonMapper_Exception
     * @since 6.1.0
     */
    private function getMappedJson($mapFromJson)
    {
        $json = (new JsonMapper())->map(
            $mapFromJson,
            new Configuration()
        );
        $this->throwWrongConfigClass($json);
        return $json->getDatabase();
    }

    /**
     * @param $json
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function throwWrongConfigClass($json)
    {
        if (get_class($json) !== Configuration::class) {
            throw new ExceptionHandler(
                sprintf(
                    '%s configuration class mismatch: %s, expected: %s.',
                    __CLASS__,
                    get_class($json),
                    Configuration::class
                ),
                Constants::LIB_DATABASE_EMPTY_JSON_CONFIG
            );
        }
    }

    /**
     * @param $identifier
     * @param bool $throwable
     * @return array
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getConnection($identifier, $throwable = true)
    {
        $currentIdentifier = $this->getCurrentIdentifier($identifier);
        $return = isset($this->connection[$currentIdentifier]) ?
            $this->connection[$currentIdentifier] : null;

        if ($throwable && is_null($return)) {
            throw new ExceptionHandler(
                sprintf(
                    'Database connection error: %s has not been initialized yet.',
                    $identifier
                ),
                Constants::LIB_DATABASE_NO_CONNECTION_INITIALIZED
            );
        }

        return $return;
    }

    /**
     * @param $connection
     * @param null $identifier
     * @return DatabaseConfig
     * @since 6.1.0
     */
    public function setConnection($connection, $identifier = null)
    {
        $this->connection[$this->getCurrentIdentifier($identifier)] = $connection;

        return $this;
    }

    /**
     * @param null $identifierName
     * @return int
     * @since 6.1.0
     */
    public function getAffectedRows($identifierName = null)
    {
        $return = null;
        $currentIdentifier = $this->getCurrentIdentifier($identifierName);

        if (isset($this->affectedRows[$currentIdentifier])) {
            $return = $this->affectedRows[$currentIdentifier];
        }

        return (int)$return;
    }

    /**
     * @param $affectedRows
     * @param null $identifierName
     * @return $this
     * @since 6.1.0
     */
    public function setAffectedRows($affectedRows, $identifierName = null)
    {
        $currentIdentifier = $this->getCurrentIdentifier($identifierName);
        $this->affectedRows[$currentIdentifier] = (int)$affectedRows;
        return $this;
    }

    /**
     * @param null $identifierName
     * @return int
     * @since 6.1.0
     */
    public function getLastInsertId($identifierName = null)
    {
        $return = null;
        $currentIdentifier = $this->getCurrentIdentifier($identifierName);

        if (isset($this->lastInsertId[$currentIdentifier])) {
            $return = $this->lastInsertId[$currentIdentifier];
        }

        return (int)$return;
    }

    /**
     * @param $insertId
     * @param null $identifierName
     * @return DatabaseConfig
     * @since 6.1.0
     */
    public function setLastInsertId($insertId, $identifierName = null)
    {
        $currentIdentifier = $this->getCurrentIdentifier($identifierName);
        $this->lastInsertId[$currentIdentifier] = (int)$insertId;

        return $this;
    }

    /**
     * @param $statement
     * @param null $identifierName
     * @return $this
     * @since 6.1.0
     */
    public function setStatement($statement, $identifierName = null)
    {
        $currentIdentifier = $this->getCurrentIdentifier($identifierName);
        $this->statements[$currentIdentifier] = $statement;

        return $this;
    }

    /**
     * @param null $identifierName
     * @return mixed|null
     * @since 6.1.0
     */
    public function getStatement($identifierName = null)
    {
        $return = null;
        $currentIdentifier = $this->getCurrentIdentifier($identifierName);
        if (isset($this->statements[$currentIdentifier])) {
            $return = $this->statements[$currentIdentifier];
        }
        return $return;
    }

    /**
     * @param null $identifier
     * @return int
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getPreferredDriver($identifier = null)
    {
        $defaultDriver = self::getDefaultDriver();
        $currentIdentifier = $this->getCurrentIdentifier($identifier);
        return isset($this->preferredDriver[$currentIdentifier]) ?
            $this->preferredDriver[$currentIdentifier] : $defaultDriver;
    }

    /**
     * @param int $preferredDriver
     * @param null $identifier
     * @return DatabaseConfig
     * @since 6.1.0
     */
    public function setPreferredDriver($preferredDriver, $identifier = null)
    {
        $this->preferredDriver[$this->getCurrentIdentifier($identifier)] = $preferredDriver;

        return $this;
    }

    /**
     * @return int
     * @since 6.1.0
     */
    public function getDefaultTimeout()
    {
        return $this->defaultTimeout;
    }

    /**
     * @param int $defaultTimeout
     * @since 6.1.0
     */
    public function setDefaultTimeout($defaultTimeout)
    {
        $this->defaultTimeout = $defaultTimeout;
    }

    /**
     * @param $identifier
     * @return int
     * @since 6.1.0
     */
    public function getTimeout($identifier)
    {
        $currentIdentifier = $this->getCurrentIdentifier($identifier);
        return isset($this->timeout[$currentIdentifier]) ?
            (int)$this->timeout[$currentIdentifier] : $this->defaultTimeout;
    }

    /**
     * @param int $timeout
     * @param null $identifier
     * @return DatabaseConfig
     * @since 6.1.0
     */
    public function setTimeout($timeout, $identifier = null)
    {
        $this->timeout[$this->getCurrentIdentifier($identifier)] = (int)$timeout;

        return $this;
    }
}
