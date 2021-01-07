<?php

/** @noinspection PhpDeprecationInspection */

/** @noinspection PhpComposerExtensionStubsInspection */

namespace TorneLIB\Module\Database\Drivers;

use Exception;
use PDO;
use PDOException;
use PDOStatement;
use TorneLIB\Config\Flag;
use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Helpers\DataHelper;
use TorneLIB\Model\Database\Drivers;
use TorneLIB\Model\Database\Types;
use TorneLIB\Model\Interfaces\DatabaseInterface;
use TorneLIB\Module\Config\DatabaseConfig;
use TorneLIB\Utils\Security;

/**
 * Class MySQL
 * @package TorneLIB\Module\Database\Drivers
 * @since 6.1.0
 */
class MySQL implements DatabaseInterface
{
    /**
     * @var DatabaseConfig $CONFIG
     * @since 6.1.0
     */
    private $CONFIG;

    /**
     * @var array $initDriver Indicates if driver is really initialized.
     * @since 6.1.0
     */
    private $initDriver = [];

    /**
     * @var DataResponseRow $responseRow
     */
    private $responseRow;

    /**
     * MySQL constructor.
     * @since 6.1.0
     */
    public function __construct()
    {
        $this->CONFIG = new DatabaseConfig();
    }

    /**
     * @param null $forceDriver
     * @param null $identifier
     * @return int
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getInitializedDriver($forceDriver = null, $identifier = null)
    {
        $this->initDriver[$this->CONFIG->getCurrentIdentifier($identifier)] = true;

        if ((is_null($forceDriver) || $forceDriver === Drivers::MYSQL_IMPROVED) &&
            Security::getCurrentFunctionState('mysqli_connect', false)
        ) {
            $this->CONFIG->setPreferredDriver(Drivers::MYSQL_IMPROVED, $identifier);
        } elseif ((is_null($forceDriver) || $forceDriver === Drivers::MYSQL_DEPRECATED) &&
            Security::getCurrentFunctionState('mysql_connect', false)
        ) {
            $this->CONFIG->setPreferredDriver(Drivers::MYSQL_DEPRECATED, $identifier);
        } elseif ((is_null($forceDriver) || $forceDriver === Drivers::MYSQL_PDO) &&
            Security::getCurrentClassState('PDO', false) &&
            DatabaseConfig::getCanPdo()
        ) {
            $this->CONFIG->setPreferredDriver(Drivers::MYSQL_PDO, $identifier);
        } else {
            throw new ExceptionHandler(
                sprintf(
                    'No database drivers is available for %s.',
                    __CLASS__
                ),
                Constants::LIB_DATABASE_DRIVER_UNAVAILABLE
            );
        }

        return $this->CONFIG->getPreferredDriver();
    }

    /**
     * @since 6.1.0
     */
    public function __destruct()
    {
        $identifiers = $this->CONFIG->getIdentifiers();

        foreach ($identifiers as $identifierName) {
            DataHelper::closeConnection($this->CONFIG, $identifierName);
        }
    }

    /**
     * @return DatabaseConfig
     * @since 6.1.0
     */
    public function getConfig()
    {
        return $this->CONFIG;
    }

    /**
     * @param DatabaseConfig $databaseConfig
     * @return $this|mixed
     * @since 6.1.0
     */
    public function setConfig($databaseConfig)
    {
        $this->CONFIG = $databaseConfig;

        return $this;
    }

    /**
     * @param $inputString
     * @param null $identifierName
     * @return string
     * @deprecated Escaping through datahelper is deprecated and should be avoided.
     * @since 6.1.0
     */
    public function escape($inputString, $identifierName = null)
    {
        try {
            $return = DataHelper::getEscaped(
                $inputString,
                $this->CONFIG->getPreferredDriver($identifierName),
                $this->CONFIG->getConnection($identifierName)
            );
        } catch (Exception $e) {
            $return = (new DataHelper())->getEscapeDeprecated($inputString);
        }

        return $return;
    }

    /**
     * @param null $identifierName
     * @return int
     * @since 6.1.0
     */
    public function getLastInsertId($identifierName = null)
    {
        return $this->CONFIG->getLastInsertId($identifierName);
    }

    /**
     * @param null $identifierName
     * @return int
     * @since 6.1.0
     */
    public function getAffectedRows($identifierName = null)
    {
        return $this->CONFIG->getAffectedRows($identifierName);
    }

    /**
     * @param string $serverIdentifier
     * @param array $serverOptions
     * @param string $serverHostAddr
     * @param string $serverUsername
     * @param string $serverPassword
     * @return mixed|void
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function connect(
        $serverIdentifier = 'default',
        $serverOptions = [],
        $serverHostAddr = '127.0.0.1',
        $serverUsername = 'tornelib',
        $serverPassword = 'tornelib1337'
    ) {
        $return = null;

        $useIdentifier = $this->CONFIG->getCurrentIdentifier($serverIdentifier);

        if (!isset($this->initDriver[$useIdentifier])) {
            $this->getInitializedDriver(null, $useIdentifier);
        }

        // Configure current connection.
        $this->setServer(
            $useIdentifier,
            $serverOptions,
            $serverHostAddr,
            $serverUsername,
            $serverPassword
        );

        switch ($this->CONFIG->getPreferredDriver($useIdentifier)) {
            case Drivers::MYSQL_IMPROVED:
                $return = $this->getConnectionImproved($useIdentifier);
                break;
            case Drivers::MYSQL_DEPRECATED:
                $return = $this->getConnectionDeprecated($useIdentifier);
                break;
            case Drivers::MYSQL_PDO:
                $return = $this->getConnectionPdo($useIdentifier);
                break;

            default:
                throw new ExceptionHandler(
                    sprintf(
                        '%s error in %s: could not find any proper driver to connect with.',
                        __FUNCTION__,
                        __CLASS__
                    ),
                    Constants::LIB_DATABASE_DRIVER_UNAVAILABLE
                );
                break;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        if (Flag::getFlag('SQLCHAIN')) {
            return $this;
        }

        return $return;
    }

    /**
     * @param $identifier
     * @param $options
     * @param $serverAddr
     * @param $serverUser
     * @param $serverPassword
     * @return $this
     * @since 6.1.0
     */
    private function setServer($identifier, $options, $serverAddr, $serverUser, $serverPassword)
    {
        $this->CONFIG->setIdentifier($identifier);
        $this->CONFIG->setServerOptions($options, $identifier);
        $this->CONFIG->setServerHost($serverAddr, $identifier);
        $this->CONFIG->setServerUser($serverUser, $identifier);
        $this->CONFIG->setServerPassword($serverPassword, $identifier);

        return $this;
    }

    /**
     * @param $identifierName
     * @return bool
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getConnectionImproved($identifierName)
    {
        $connection = @mysqli_connect(
            $this->getServerHost($identifierName),
            $this->getServerUser($identifierName),
            $this->getServerPassword($identifierName),
            $this->getDatabase($identifierName, false),
            $this->getServerPort($identifierName)
        );

        $this->getDatabaseError(mysqli_connect_error(), mysqli_connect_errno(), __FUNCTION__);
        $this->getDatabaseError(
            mysqli_error($connection),
            mysqli_errno($connection),
            __FUNCTION__
        );

        if (!empty($connection)) {
            $this->CONFIG->setConnection(
                $connection,
                $identifierName
            );
        }
        $this->setLocalServerOptions($identifierName);

        return is_object($connection);
    }

    /**
     * @inheritDoc
     * @since 6.1.0
     */
    public function getServerHost($identifierName = null)
    {
        return $this->CONFIG->getServerHost($identifierName);
    }

    /**
     * @param null $identifierName
     * @return string
     * @since 6.1.0
     */
    public function getServerUser($identifierName = null)
    {
        return $this->CONFIG->getServerUser($identifierName);
    }

    /**
     * @param null $identifierName
     * @return string
     * @since 6.1.0
     */
    public function getServerPassword($identifierName = null)
    {
        return $this->CONFIG->getServerPassword($identifierName);
    }

    /**
     * @param $identifierName
     * @param bool $throwable
     * @return string
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getDatabase($identifierName = null, $throwable = false)
    {
        return $this->CONFIG->getDatabase($identifierName, $throwable);
    }

    /**
     * @param null $identifierName
     * @return int|string
     * @since 6.1.0
     */
    public function getServerPort($identifierName = null)
    {
        return $this->CONFIG->getServerPort($identifierName);
    }

    /**
     * @param $message
     * @param $code
     * @param $fromFunction
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getDatabaseError($message, $code, $fromFunction)
    {
        if ((int)$code) {
            $this->throwDatabaseException(
                $message,
                $code,
                null,
                $fromFunction
            );
        }
    }

    /**
     * @param $message
     * @param $code
     * @param $previousException
     * @param $fromFunction
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function throwDatabaseException($message, $code, $previousException, $fromFunction)
    {
        throw new ExceptionHandler(
            $message,
            $code,
            $previousException,
            null,
            $fromFunction,
            $this
        );
    }

    /**
     * @param string $identifier
     * @return mixed|void
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function setLocalServerOptions($identifier)
    {
        $this->CONFIG->setServerOptions(
            [
                defined(MYSQLI_OPT_CONNECT_TIMEOUT) ?
                    MYSQLI_OPT_CONNECT_TIMEOUT : 0 => $this->CONFIG->getTimeout($identifier),
            ],
            $identifier
        );

        if (is_object($this->CONFIG->getConnection($identifier))) {
            foreach ($this->CONFIG->getServerOptions($identifier) as $optionKey => $optionValue) {
                /** @noinspection PhpParamsInspection */
                mysqli_options(
                    $this->CONFIG->getConnection($identifier),
                    $optionKey,
                    $optionValue
                );
            }
        }

        return true;
    }

    /**
     * @param $identifierName
     * @return bool
     * @throws ExceptionHandler
     * @noinspection PhpUndefinedMethodInspection
     * @since 6.1.0
     */
    private function getConnectionDeprecated($identifierName)
    {
        // Special occasions.
        if (!Flag::getFlag('SQL_NEW_LINK')) {
            Flag::setFlag('SQL_NEW_LINK');
        }

        $connection = @mysql_connect(
            $this->getServerHost($identifierName),
            $this->getServerUser($identifierName),
            $this->getServerPassword($identifierName),
            Flag::getFlag('SQL_NEW_LINK')
        );

        if (is_resource($connection)) {
            $this->CONFIG->setConnection(
                $connection,
                $identifierName
            );
        }

        if (!$connection) {
            $errorMessage = empty(mysql_error()) ? sprintf(
                'Could not connect with deprecated driver to mysql server at %s.',
                $this->CONFIG->getServerHost()
            ) : mysql_error();
            $errorCode = !mysql_errno() ? Constants::LIB_DATABASE_CONNECTION_EXCEPTION : mysql_errno();
            $this->getDatabaseError($errorMessage, $errorCode, __FUNCTION__);
        } else {
            $this->getDatabaseError(
                mysql_error($connection),
                mysql_errno($connection),
                __FUNCTION__
            );
        }

        return is_resource($connection);
    }

    /**
     * @param $identifierName
     * @return bool
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getConnectionPdo($identifierName)
    {
        $connection = null;
        $DSN = sprintf(
            'mysql:dbname=%s;host=%s',
            $this->CONFIG->getDatabase($identifierName, false),
            $this->CONFIG->getServerHost($identifierName)
        );

        $PDOException = null;
        try {
            $connection = new PDO(
                $DSN,
                $this->getServerUser($identifierName),
                $this->getServerPassword($identifierName),
                $this->getServerOptions($identifierName)
            );
        } catch (PDOException $PDOException) {
            // Wait for it.
        }

        if (is_object($connection)) {
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->CONFIG->setConnection($connection, $identifierName);
        } else {
            $this->getPdoError($connection, $PDOException);
        }

        return is_object($connection);
    }

    /**
     * @param null $identifierName
     * @return mixed
     * @since 6.1.0
     */
    public function getServerOptions($identifierName = null)
    {
        return $this->CONFIG->getServerOptions($identifierName);
    }

    /**
     * @param $connection
     * @param $PDOException
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getPdoError($connection, $PDOException)
    {
        $errorMessage = sprintf(
            'Could not connect to PDO server at %s.',
            $this->CONFIG->getServerHost()
        );

        $errorCode = Constants::LIB_DATABASE_CONNECTION_EXCEPTION;

        if (!empty($connection) && method_exists($connection, 'errorInfo')) {
            $errorMessage = implode(' ', $connection->errorInfo());
        }
        if (get_class($PDOException) === 'PDOException') {
            $errorMessage = $PDOException->getMessage();
            if ((int)$validIntegerError = $PDOException->getCode()) {
                $errorCode = $validIntegerError;
            }
        }
        if ($errorCode) {
            $this->throwDatabaseException(
                $errorMessage,
                $errorCode,
                $PDOException,
                __FUNCTION__
            );
        }
    }

    /**
     * @param null $identifier
     * @return int
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getPreferredDriver($identifier = null)
    {
        return $this->CONFIG->getPreferredDriver($identifier);
    }

    /**
     * @return mixed
     * @since 6.1.0
     */
    public function getConnection()
    {
        return $this;
    }

    /**
     * @param int $preferredDriver
     * @param null $identifier
     * @return int
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function setPreferredDriver($preferredDriver = Drivers::MYSQL_IMPROVED, $identifier = null)
    {
        $this->CONFIG->setPreferredDriver($preferredDriver, $identifier);

        return $this->getInitializedDriver($preferredDriver, $identifier);
    }

    /**
     * @param $schemaName
     * @param $identifierName
     * @return $this|mixed
     * @throws ExceptionHandler
     * @noinspection PhpParamsInspection
     * @since 6.1.0
     */
    public function setDatabase($schemaName, $identifierName = null)
    {
        $useIdentifier = $this->CONFIG->getCurrentIdentifier($identifierName);
        $this->CONFIG->setDatabase($schemaName, $useIdentifier);

        // Ignore unexistent connections if not set, as this could be running in pre-init.
        if (!empty($connection = $this->CONFIG->getConnection($useIdentifier, false))) {
            if ($this->CONFIG->getPreferredDriver($useIdentifier) === Drivers::MYSQL_IMPROVED) {
                if (!mysqli_select_db($connection, $schemaName)) {
                    $this->getDatabaseError(
                        mysqli_error($connection),
                        mysqli_errno($connection),
                        __FUNCTION__
                    );
                }
            } elseif ($this->CONFIG->getPreferredDriver($useIdentifier) === Drivers::MYSQL_DEPRECATED) {
                if (!mysql_select_db($schemaName, $connection)) {
                    $this->getDatabaseError(
                        mysql_error($connection),
                        mysql_errno($connection),
                        __FUNCTION__
                    );
                }
            } elseif ($this->CONFIG->getPreferredDriver($useIdentifier) === Drivers::MYSQL_PDO) {
                if (!empty($connection) && method_exists($connection, "select_db")) {
                    $connection->select_db($schemaName);
                } else {
                    // Very specific for PDO.
                    /** @noinspection PhpUndefinedMethodInspection */
                    $connection->query("use " . $schemaName);
                }
            }
        }

        return $this;
    }

    /**
     * @param string $identifierName
     * @return $this
     * @since 6.1.0
     */
    public function setIdentifier($identifierName)
    {
        $this->CONFIG->setIdentifier($identifierName);

        return $this;
    }

    /**
     * @return string
     * @since 6.1.0
     */
    public function getIdentifier()
    {
        return $this->CONFIG->getIdentifier();
    }

    /**
     * @param int $portNumber
     * @param null $identifierName
     * @return $this
     * @since 6.1.0
     */
    public function setServerPort($portNumber, $identifierName = null)
    {
        $this->CONFIG->setServerPort($portNumber, $identifierName);

        return $this;
    }

    /**
     * @param string $serverHost
     * @param null $identifierName
     * @return mixed|DatabaseConfig
     * @since 6.1.0
     */
    public function setServerHost(
        $serverHost,
        $identifierName = null
    ) {
        return $this->CONFIG->setServerHost($serverHost, $identifierName);
    }

    /**
     * @param $userName
     * @param null $identifierName
     * @return DatabaseConfig
     * @since 6.1.0
     */
    public function setServerUser($userName, $identifierName = null)
    {
        return $this->CONFIG->setServerUser($userName, $identifierName);
    }

    /**
     * @param $userName
     * @param null $identifierName
     * @return DatabaseConfig
     */
    public function setServerPassword($userName, $identifierName = null)
    {
        return $this->CONFIG->setServerPassword($userName, $identifierName);
    }

    /**
     * @param int $serverType
     * @param null $identifierName
     * @return DatabaseConfig
     * @since 6.1.0
     */
    public function setServerType($serverType = Types::MYSQL, $identifierName = null)
    {
        return $this->CONFIG->setServerType($serverType, $identifierName);
    }

    /**
     * @param null $identifierName
     * @return Types
     */
    public function getServerType($identifierName = null)
    {
        return $this->CONFIG->getServerType($identifierName);
    }

    /**
     * @param $serverOptions
     * @param null $identifierName
     * @return mixed
     * @since 6.1.0
     */
    public function setServerOptions($serverOptions, $identifierName = null)
    {
        return $this->CONFIG->setServerOptions($serverOptions, $identifierName);
    }

    /**
     * @param null $identifierName
     * @return mixed|null
     * @since 6.1.0
     */
    public function getStatement($identifierName = null)
    {
        return $this->CONFIG->getStatement($identifierName);
    }

    /**
     * @param string $queryString
     * @param array $parameters
     * @param null $identifierName
     * @param bool $assoc
     * @return mixed|void
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getFirst($queryString, $parameters = [], $identifierName = null, $assoc = true)
    {
        $return = null;
        if ($this->setQuery($queryString, $parameters, $identifierName)) {
            $return = $this->getRow(null, $identifierName, $assoc);
        }

        return $return;
    }

    /**
     * @param null $querystring
     * @param array $parameters
     * @return DataResponseRow
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getResponseRow($querystring = null, $parameters = [])
    {
        if (!empty($this->responseRow) && empty($querystring)) {
            $return = $this->responseRow;
        } else {
            $this->getFirst($querystring, $parameters);
            $return = $this->responseRow;
        }
        return $return;
    }

    /**
     * @param $querystring
     * @param $parameters
     * @return array|mixed|void|null
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function query_first($querystring, $parameters = [])
    {
        return $this->getFirst($querystring, $parameters);
    }

    /**
     * @param $queryString
     * @param array $parameters
     * @param null $identifierName
     * @return bool|mixed|resource
     * @throws ExceptionHandler
     * @deprecate Use setQuery.
     * @since 6.1.0
     */
    public function query($queryString, $parameters = [], $identifierName = null)
    {
        return $this->setQuery($queryString, $parameters, $identifierName);
    }

    /**
     * @param string $queryString
     * @param array $parameters
     * @param string $identifierName
     * @return mixed
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function setQuery($queryString, $parameters = [], $identifierName = null)
    {
        $return = null;
        $useIdentifier = $this->CONFIG->getCurrentIdentifier($identifierName);

        switch ($this->CONFIG->getPreferredDriver($useIdentifier)) {
            case Drivers::MYSQL_IMPROVED:
                $return = $this->setQueryImproved(
                    $queryString,
                    $this->getParameters($parameters),
                    $useIdentifier
                );
                break;
            case Drivers::MYSQL_DEPRECATED:
                $return = $this->setQueryDeprecated(
                    $queryString,
                    $this->getParameters($parameters),
                    $useIdentifier
                );
                break;
            case Drivers::MYSQL_PDO:
                $return = $this->setQueryPdo(
                    $queryString,
                    $this->getParameters($parameters),
                    $useIdentifier
                );
                break;
            default:
                break;
        }

        if (is_null($return)) {
            throw new ExceptionHandler(
                sprintf(
                    '%s error in %s: could not find any proper query driver or query failed.',
                    __FUNCTION__,
                    __CLASS__
                ),
                Constants::LIB_DATABASE_DRIVER_UNAVAILABLE
            );
        }

        return $return;
    }

    /**
     * Queries in 6.1.0 is always based on prepares, not raw.
     * @param $queryString
     * @param $parameters
     * @param null $identifierName
     * @return null
     * @throws ExceptionHandler
     * @since 6.1.0
     * @noinspection PhpParamsInspection
     */
    private function setQueryImproved($queryString, $parameters = [], $identifierName = null)
    {
        $return = null;

        $useIdentifier = $this->CONFIG->getCurrentIdentifier($identifierName);
        $preparedStatement = mysqli_prepare(
            $this->CONFIG->getConnection($useIdentifier),
            $queryString
        );

        if (!empty($preparedStatement)) {
            $this->setPreparedStatement($preparedStatement, $parameters);
            // Laying our trust in straight forward PHP >5.3 responses.
            mysqli_stmt_execute($preparedStatement);
            $this->CONFIG->setResult($preparedStatement->get_result(), $identifierName);

            if (is_object($preparedStatement)) {
                $return = $this->getDataFromImproved(
                    $preparedStatement,
                    $useIdentifier
                );
            }
        }

        $this->getDatabaseError(
            mysqli_error($this->CONFIG->getConnection($useIdentifier)),
            mysqli_errno($this->CONFIG->getConnection($useIdentifier)),
            __FUNCTION__
        );

        return $return;
    }

    /**
     * @param $statement
     * @param $parameters
     * @return array
     * @since 6.1.0
     */
    private function setPreparedStatement($statement, $parameters)
    {
        $return = [$statement, str_pad("", count($parameters), "s")];
        foreach ($this->getParameters($parameters) as $key => $value) {
            $return[] =& $parameters[$key];
        }
        if (count($parameters)) {
            mysqli_stmt_bind_param(...$return);
        }

        return $return;
    }

    /**
     * @param array $parameters
     * @return array
     * @since 6.1.0
     */
    private function getParameters($parameters = [])
    {
        if (!is_array($parameters)) {
            $parameters = (array)$parameters;
        }
        return $parameters;
    }

    /**
     * @param $statement
     * @param $identifierName
     * @return bool
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getDataFromImproved($statement, $identifierName = null)
    {
        $this->CONFIG->setStatement($statement, $identifierName);
        /** @noinspection PhpParamsInspection */
        $this->CONFIG->setLastInsertId(
            mysqli_insert_id(
                $this->CONFIG->getConnection($identifierName)
            ),
            $identifierName
        );
        if (isset($statement->affected_rows)) {
            $this->CONFIG->setAffectedRows($statement->affected_rows, $identifierName);
        }

        return ($this->CONFIG->getAffectedRows($identifierName) || $this->CONFIG->getLastInsertId($identifierName));
    }

    /**
     * Query with deprecated driver (Unsafe!).
     * @param $queryString
     * @param array $parameters
     * @param null $identifierName
     * @return bool|resource
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function setQueryDeprecated($queryString, $parameters = [], $identifierName = null)
    {
        $return = null;
        $useIdentifier = $this->CONFIG->getCurrentIdentifier($identifierName);

        $halfSafeString = $this->getHalfSafeString(
            $queryString,
            $parameters
        );
        $queryResponse = mysql_query(
            $halfSafeString,
            $this->CONFIG->getConnection($useIdentifier)
        );

        $this->getDatabaseError(
            mysql_error($this->CONFIG->getConnection($useIdentifier)),
            mysql_errno($this->CONFIG->getConnection($useIdentifier)),
            __FUNCTION__
        );

        $this->CONFIG->setResult($queryResponse, $identifierName);
        if ($properReturn = $this->getDataFromDeprecated($queryResponse, $useIdentifier)) {
            $return = $properReturn;
        }

        return $return;
    }

    /**
     * @param $queryString
     * @param array $parameters
     * @return string
     * @since 6.1.0
     */
    public function getHalfSafeString($queryString, $parameters)
    {
        $queryString = preg_replace('/ \?$/', ' %s', $queryString);
        $queryString = preg_replace("/ \? /", ' %s ', $queryString);

        $newArray = [];
        foreach ($parameters as $key => $value) {
            $newArray[$key] = sprintf("'%s'", mysql_real_escape_string($value));
        }

        return sprintf(
            $queryString,
            ...$newArray
        );
    }

    /**
     * @param resource $statement
     * @param $identifierName
     * @return bool
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getDataFromDeprecated($statement, $identifierName = null)
    {
        $this->CONFIG->setStatement($statement, $identifierName);
        $this->CONFIG->setLastInsertId(
            mysql_insert_id($this->CONFIG->getConnection($identifierName)),
            $identifierName
        );
        $this->CONFIG->setAffectedRows(
            mysql_affected_rows($this->CONFIG->getConnection($identifierName)),
            $identifierName
        );

        return (
            $this->CONFIG->getAffectedRows($identifierName) ||
            $this->CONFIG->getLastInsertId($identifierName) ||
            !mysql_errno($this->CONFIG->getConnection($identifierName))
        );
    }

    /**
     * @param $queryString
     * @param array $parameters
     * @param null $identifierName
     * @return null
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function setQueryPdo($queryString, $parameters = [], $identifierName = null)
    {
        /** @var PDO $connection */
        $connection = $this->CONFIG->getConnection($identifierName);
        /** @var PDOStatement $statementPrepare */
        $statementPrepare = $connection->prepare($queryString);
        $return = $statementPrepare->execute($parameters);
        $this->CONFIG->setResult($return, $identifierName);

        $this->getDatabaseError(
            implode(', ', $connection->errorInfo()),
            $connection->errorCode(),
            __FUNCTION__
        );

        $this->getDataFromPdo($statementPrepare, $identifierName);

        return ($return ||
            $this->CONFIG->getLastInsertId($identifierName) ||
            $this->CONFIG->getAffectedRows($identifierName)
        );
    }

    /**
     * @param $statement
     * @param null $identifierName
     * @return bool
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    private function getDataFromPdo($statement, $identifierName = null)
    {
        $connection = $this->CONFIG->getConnection($identifierName);
        $this->CONFIG->setStatement($statement, $identifierName);
        $this->CONFIG->setLastInsertId($connection->lastInsertId(), $identifierName);
        $this->CONFIG->setAffectedRows($statement->rowCount(), $identifierName);

        return (
            $this->CONFIG->getAffectedRows($identifierName) ||
            $this->CONFIG->getLastInsertId($identifierName)
        );
    }

    /**
     * One database fetcher.
     * @param $resource
     * @param bool $assoc
     * @return mixed|void
     * @deprecated Use getRow instead().
     * @since 6.1.0
     */
    public function fetch($resource = null, $assoc = true)
    {
        return $this->getRow($resource, $this->CONFIG->getCurrentIdentifier(), $assoc);
    }

    /**
     * @param resource $resource Not necessary.
     * @param null $identifierName
     * @param bool $assoc
     * @return mixed|void
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getRow($resource = null, $identifierName = null, $assoc = true)
    {
        $return = null;
        $testConnection = $this->getProperResource($resource);

        // Initialize primary data values to use. Users are on their own here,
        // if they are not using the identifierSystem.
        $result = $resource;

        // For PDO
        $statement = null;

        if (!$testConnection) {
            $result = $this->CONFIG->getResult($identifierName);
            $statement = $this->CONFIG->getStatement($identifierName);
        }

        switch ($this->CONFIG->getPreferredDriver($identifierName)) {
            case Drivers::MYSQL_IMPROVED:
                if ($assoc) {
                    $return = mysqli_fetch_assoc($result);
                } elseif ((int)$assoc === 2) {
                    $return = mysqli_fetch_array($result);
                } else {
                    $return = mysqli_fetch_object($result);
                }
                break;
            case Drivers::MYSQL_DEPRECATED:
                if ($assoc) {
                    $return = mysql_fetch_assoc($result);
                } else {
                    $return = mysql_fetch_object($result);
                }
                break;
            case Drivers::MYSQL_PDO:
                if (!empty($statement) && method_exists($statement, 'fetchObject')) {
                    if ($assoc) {
                        // Fetch an object and cast it as an array.
                        $return = (array)$statement->fetchObject();
                    } else {
                        $return = $statement->fetchObject();
                    }
                }
                break;
            default:
                break;
        }

        if ($assoc === 3) {
            $this->responseRow = new DataResponseRow();
            $this->responseRow->setResponse($return);
        }

        return $assoc !== 3 ? $return : $this->responseRow;
    }

    /**
     * @param $resource
     * @param null $identifierName
     * @return bool|DatabaseConfig
     */
    private function getProperResource($resource, $identifierName = null)
    {
        $return = false;

        if (!is_null($resource) && get_class($resource) === 'mysqli') {
            $return = $this->CONFIG->setPreferredDriver(Drivers::MYSQL_IMPROVED, $identifierName);
        }

        return $return;
    }
}
