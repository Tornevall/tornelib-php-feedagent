<?php

namespace TorneLIB\Model\Database;

/**
 * Class Types
 * @package TorneLIB\Model\Database
 * @since 6.1.0
 */
class Types
{
    const MYSQL = 1;
    const SQLITE3 = 2;
    const PGSQL = 3;
    const ODBC = 4;
    const MSSQL = 5;
    const PDO = 6;

    const NOT_IMPLEMENTED = 65535;
}
