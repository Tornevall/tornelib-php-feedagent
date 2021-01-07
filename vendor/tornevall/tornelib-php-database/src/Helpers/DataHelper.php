<?php

/** @noinspection PhpComposerExtensionStubsInspection */
/** @noinspection PhpDeprecationInspection */

namespace TorneLIB\Helpers;

use Exception;
use TorneLIB\Model\Database\Drivers;

/**
 * Class DataHelper
 * @package TorneLIB\Helpers
 * @since 6.1.0
 */
class DataHelper
{
    /**
     * @param string $inputString
     * @param int $driverType
     * @param null $resource
     * @return mixed|string|null
     * @since 6.1.0
     * @deprecated Escaping through datahelper is deprecated and should be avoided.
     */
    public static function getEscaped($inputString = '', $driverType = Drivers::MYSQL_IMPROVED, $resource = null)
    {
        return (new self())->escape($inputString, $driverType, $resource);
    }

    /**
     * SQL escaping, v6.0-style.
     * @param string $inputString
     * @param int $driverType
     * @param null $resource
     * @return mixed|string
     * @since 6.0.0
     * @deprecated Use proper drivers.
     * @noinspection PhpComposerExtensionStubsInspection
     */
    public function escape($inputString = '', $driverType = Drivers::MYSQL_IMPROVED, $resource = null)
    {
        $return = null;
        // PGSQL: pg_escape_literal($this->escape_deprecated($injectionString))
        // MSSQL: preg_replace("[']", "''", $this->escape_deprecated($injectionString));

        if ($driverType === Drivers::MYSQL_IMPROVED) {
            $return = @mysqli_real_escape_string($resource, $this->getEscapeDeprecated($inputString));
        } elseif ($driverType === Drivers::MYSQL_PDO) {
            // The weakest way of stripping something
            $quotedString = $resource->quote($inputString);
            /** @noinspection NotOptimalRegularExpressionsInspection */
            $return = preg_replace("@^'|'$@is", '', $quotedString);
        } elseif (Drivers::MYSQL_IMPROVED === $driverType) {
            $return = @mysql_real_escape_string(
                $resource,
                $this->getEscapeDeprecated($inputString)
            );
        }

        return $return;
    }

    /**
     * Compatibility mode for magic_quotes - DEPRECATED as of PHP 5.3.0 and REMOVED as of PHP 5.4.0
     * This method will be passed only if necessary
     * @link http://php.net/manual/en/security.magicquotes.php Security Magic Quotes
     * @param null $inputString
     * @return null|string
     * @deprecated Use proper drivers.
     * @since 6.0.0
     */
    public function getEscapeDeprecated($inputString = null)
    {
        if (PHP_VERSION_ID <= 50300 &&
            function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
            $inputString = stripslashes($inputString);
        }

        return addslashes($inputString);
    }

    /**
     * Destructor helper. Closing connections that has not been closed yet.
     * @param $config
     * @param null $identifierName
     * @return bool
     * @since 6.1.0
     */
    public static function closeConnection($config, $identifierName = null)
    {
        $return = false;
        $currentIdentifier = $config->getCurrentIdentifier($identifierName);
        $currentDriver = $config->getPreferredDriver($currentIdentifier);
        try {
            $connection = $config->getConnection($currentIdentifier);
        } catch (Exception $e) {
            // No connections won't need closing.
            return $return;
        }

        switch ($currentDriver) {
            case Drivers::MYSQL_IMPROVED:
                $return = @mysqli_close($connection);
                break;
            case Drivers::MYSQL_DEPRECATED:
                $return = @mysql_close($connection);
                break;
            default:
                // Not normally closing PDO.
                $return = true;
                break;
        }

        return $return;
    }
}
