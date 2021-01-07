<?php

/** @noinspection PhpComposerExtensionStubsInspection */

/** @noinspection PhpDeprecationInspection */

namespace TorneLIB\Module;

use Exception;
use JsonMapper_Exception;
use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Helpers\Version;
use TorneLIB\Model\Database\Drivers;
use TorneLIB\Model\Database\Ports;
use TorneLIB\Model\Database\Servers;
use TorneLIB\Model\Database\Types;
use TorneLIB\Module\Config\DatabaseConfig;
use TorneLIB\Module\Database\Drivers\MySQL;
use TorneLIB\MODULE_DATABASE;

require_once(__DIR__ . '/../vendor/autoload.php');

@unlink(__DIR__ . '/config.json');

// Initializer.
if (!file_exists(__DIR__ . '/config.json')) {
    @copy(
        __DIR__ . '/config.json.sample',
        __DIR__ . '/config.json'
    );
}

class DatabaseTest extends TestCase
{
    private $serverhost = '127.0.0.1';
    private $username = 'tornelib';
    private $password = 'tornelib1337';
    private $database = 'tornelib_tests';

    /**
     * @test
     */
    public function initializer()
    {
        static::assertInstanceOf(
            MySQL::class,
            (new MySQL())
        );
    }

    /**
     * @test
     * @throws Exception
     * @noinspection DynamicInvocationViaScopeResolutionInspection
     */
    public function theVersion()
    {
        /** @noinspection PhpParamsInspection */
        static::expectException(Exception::class);

        Version::getRequiredVersion('9999');
    }

    /**
     * @test
     */
    public function setIdentifier()
    {
        $SQL = (new MySQL());
        static::assertEquals(
            'theIdentifier',
            $SQL->setIdentifier('theIdentifier')->getIdentifier()
        );

        $identifiers = $SQL->getConfig()->getIdentifiers();
        static::assertCount(1, $identifiers);
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function setDbIdentifier()
    {
        $fail = false;
        $first = (new DatabaseConfig())->setDatabase('tests', 'test')->getDatabase('test');
        $second = (new DatabaseConfig())->setDatabase('tests')->getDatabase();
        try {
            // If using something else than the default identifier, requesting database name will fail.
            (new DatabaseConfig())->setDatabase('tests', 'test')->getDatabase();
        } catch (ExceptionHandler $e) {
            $fail = true;
        }

        static::assertTrue(
            $first === 'tests' &&
            $second === 'tests' &&
            $fail
        );
    }

    /**
     * @test
     */
    public function setServerPort()
    {
        static::assertEquals('3300', (new MySQL())->setServerPort('3300')->getServerPort());
    }

    /**
     * @test
     */
    public function getDefaultServerPort()
    {
        static::assertEquals(Ports::MYSQL, (new MySQL())->getServerPort());
    }

    /**
     * @test
     */
    public function setServerHost()
    {
        static::assertEquals('la-cool-host', (new MySQL())->setServerHost('la-cool-host')->getServerHost());
    }

    /**
     * @test
     */
    public function getDefaultServerHost()
    {
        static::assertEquals('127.0.0.1', (new MySQL())->getServerHost());
    }

    /**
     * @test
     */
    public function setServerUser()
    {
        static::assertEquals('root', (new MySQL())->setServerUser('root')->getServerUser());
    }

    /**
     * @test
     */
    public function setServerPassword()
    {
        static::assertEquals('covid-19', (new MySQL())->setServerPassword('covid-19')->getServerPassword());
    }

    /**
     * @test
     */
    public function setServerUserByIdentifier()
    {
        static::assertEquals(
            'kalle',
            (new MySQL())->setIdentifier('irregular')
                ->setServerUser('kalle', 'irregular')
                ->getServerUser('irregular')
        );
    }

    /**
     * @test
     */
    public function getDefaultServerUser()
    {
        static::assertEquals(null, (new MySQL())->getServerUser());
    }

    /**
     * @test
     */
    public function getDefaultServerType()
    {
        static::assertEquals(Types::MYSQL, (new MySQL())->getServerType());
    }

    /**
     * @test
     */
    public function getMssqlServerType()
    {
        static::assertEquals(Types::MSSQL, (new MySQL())->setServerType(Types::MSSQL)->getServerType());
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getCallFromDeprecatedModule()
    {
        $unimpl = false;
        try {
            (new MODULE_DATABASE())->setServerType(Types::NOT_IMPLEMENTED)->getServerType();
        } catch (ExceptionHandler $e) {
            $unimpl = $e->getCode() === Constants::LIB_DATABASE_NOT_IMPLEMENTED;
        }

        $db = new MODULE_DATABASE();
        $db->setServerType(Types::MYSQL);
        static::assertTrue(
            $unimpl &&
            get_class($db) === MODULE_DATABASE::class &&
            get_class($db->getHandle()) === MySQL::class
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     * @throws JsonMapper_Exception
     */
    public function getConfigStates()
    {
        $conf = (new DatabaseConfig())->getConfig(__DIR__ . '/config.json');
        $notThere = null;
        $emptyJson = null;
        try {
            (new DatabaseConfig())->getConfig('not-there');
        } catch (ExceptionHandler $e) {
            $notThere = $e->getCode();
        }

        try {
            (new DatabaseConfig())->getConfig(__DIR__ . '/empty.txt');
        } catch (ExceptionHandler $e) {
            $emptyJson = $e->getCode();
        }

        static::assertTrue(
            get_class($conf) === Servers::class &&
            $notThere === 404 &&
            $emptyJson === Constants::LIB_DATABASE_EMPTY_JSON_CONFIG
        );
    }

    /**
     * @test
     * @testdox This test requires that all drivers is installed.
     * @throws ExceptionHandler
     */
    public function forceGetDriver()
    {
        $sql = new MySQL();
        $preferred = $sql->getPreferredDriver();
        $sql->setPreferredDriver(Drivers::MYSQL_PDO);
        $newPreferred = $sql->getPreferredDriver();

        static::assertTrue(
            $preferred === Drivers::MYSQL_IMPROVED &&
            $newPreferred === Drivers::MYSQL_PDO
        );
    }

    /**
     * @test
     * @param bool $helper
     * @return MySQL
     * @throws ExceptionHandler
     */
    public function connectDefault($helper = false)
    {
        // Return $this instead of boolean.
        //Flag::setFlag('SQLCHAIN', true);
        $sql = (new MySQL())->connect();
        $configured = new MySQL();
        $configured->connect(
            'manual',
            null,
            $this->serverhost,
            $this->username,
            $this->password
        );
        $configured->setDatabase('tornelib_tests');
        $switched = $configured->getDatabase();

        if ($helper) {
            return $configured;
        }

        static::assertTrue(
            $sql &&
            $switched === 'tornelib_tests'
        );

        return $configured;
    }

    /**
     * @test
     * @throws ExceptionHandler
     * @noinspection DynamicInvocationViaScopeResolutionInspection
     * @noinspection PhpParamsInspection
     */
    public function connectMysqlIFail()
    {
        static::expectException(ExceptionHandler::class);
        (new MySQL())->connect(
            'manual',
            null,
            '127.0.0.1',
            sprintf('fail%s', sha1(uniqid('', true))),
            'tornelib1337'
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function connectDeprecatedSuccess()
    {
        if (PHP_VERSION_ID >= 70000) {
            static::markTestSkipped('Unable to perform test: Deprecated driver was removed from PHP 7.0 and above.');
            return;
        }
        $sql = new MySQL();
        $sql->setPreferredDriver(Drivers::MYSQL_DEPRECATED);
        static::assertTrue($sql->connect());
    }

    /**
     * @test
     * @throws ExceptionHandler
     * @noinspection DynamicInvocationViaScopeResolutionInspection
     * @noinspection PhpParamsInspection
     */
    public function connectFailDeprecated()
    {
        if (PHP_VERSION_ID >= 70000) {
            static::markTestSkipped('Unable to perform test: Deprecated driver was removed from PHP 7.0 and above.');
            return;
        }

        static::expectException(ExceptionHandler::class);

        $sql = new MySQL();
        $sql->setPreferredDriver(Drivers::MYSQL_DEPRECATED);
        $sql->connect(
            null,
            null,
            '127.0.0.1',
            sprintf('fail%s', sha1(uniqid('', true))),
            'tornelib1337'
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function connectManualSuccess()
    {
        $configured = new MySQL();
        $isConnected = $configured->connect(
            null,
            null,
            $this->serverhost,
            $this->username,
            $this->password
        );
        $configured->setDatabase('tornelib_tests');
        static::assertTrue((bool)$isConnected);
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function connectPdo()
    {
        $sql = new MySQL();
        $sql->setPreferredDriver(Drivers::MYSQL_PDO);
        static::assertTrue($sql->connect());
    }

    /**
     * @test
     * @throws ExceptionHandler
     * @noinspection DynamicInvocationViaScopeResolutionInspection
     * @noinspection PhpParamsInspection
     */
    public function connectPdoFail()
    {
        static::expectException(ExceptionHandler::class);

        $sql = new MySQL();
        $sql->setPreferredDriver(Drivers::MYSQL_PDO);
        $isConnected = $sql->connect(
            null,
            null,
            '127.0.0.1',
            sprintf('fail%s', sha1(uniqid('', true))),
            'tornelib1337'
        );
        static::assertTrue((bool)$isConnected);
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function connectPdoDeprecatedModule()
    {
        $sql = new MODULE_DATABASE();
        $sql->setServerType(Types::MYSQL);
        $sql->setPreferredDriver(Drivers::MYSQL_PDO);
        static::assertTrue(
            $sql->connect(
                null,
                null,
                $this->serverhost,
                $this->username,
                $this->password
            )
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getModImprovedQuery()
    {
        /** @var MODULE_DATABASE $module */
        if ($module = $this->getConnection(new MODULE_DATABASE())) {
            $queryResult = $module->setQuery(
                'SELECT * FROM tests'
            );

            static::assertTrue($queryResult);
        }
    }

    /**
     * Generic connector.
     * @param MODULE_DATABASE|MySQL $module
     * @param int $preferredDriver
     * @return mixed
     * @throws ExceptionHandler
     */
    private function getConnection($module, $preferredDriver = Drivers::MYSQL_IMPROVED)
    {
        $module->setPreferredDriver($preferredDriver);
        $module->setDatabase($this->database);
        $module->connect(
            'default',
            [],
            $this->serverhost,
            $this->username,
            $this->password
        );
        $module->setDatabase($this->database);

        return $module->getConnection();
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getModDepQuery()
    {
        if (PHP_VERSION_ID >= 70000) {
            static::markTestSkipped('Unable to perform test: Deprecated driver was removed from PHP 7.0 and above.');
            return;
        }
        /** @var MODULE_DATABASE $module */
        if ($module = $this->getConnection(new MODULE_DATABASE(), Drivers::MYSQL_DEPRECATED)) {
            $module->setPreferredDriver(Drivers::MYSQL_DEPRECATED);
            $queryResult = $module->setQuery(
                'SELECT * FROM tests WHERE data LIKE ?',
                "%"
            );

            static::assertTrue($queryResult);
        }
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getDirectImprovedQuery()
    {
        /** @var MySQL $module */
        if ($module = $this->getConnection(new MySQL())) {
            $queryResult = $module->setQuery(
                'SELECT * FROM tests'
            );

            static::assertTrue($queryResult);
        }
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getModPdoQuery()
    {
        /** @var MODULE_DATABASE $module */
        if ($module = $this->getConnection(new MODULE_DATABASE(), Drivers::MYSQL_PDO)) {
            $queryResult = $module->setQuery(
                'SELECT * FROM tests'
            );

            static::assertTrue($queryResult && $module->getAffectedRows());
        }
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getDirectPdoQuery()
    {
        /** @var MySQL $module */
        if ($module = $this->getConnection(new MySQL(), Drivers::MYSQL_PDO)) {
            $queryResult = $module->setQuery(
                'SELECT * FROM tests'
            );

            static::assertTrue($queryResult);
        }
    }

    /**
     * @test For those who badly need the old style escaping.
     */
    public function getInjection()
    {
        $escapeFirst = (new MODULE_DATABASE())->escape("'");
        $escapeSecond = (new MySQL())->escape("'");

        static::assertTrue(
            $escapeFirst === "\'" &&
            $escapeSecond === "\'"
        );
    }

    /**
     * @test
     * Using deprecated query method.
     * @throws ExceptionHandler
     */
    public function getRowImprovedSql()
    {
        $this->insertRows();

        /** @var MySQL $connection */
        $connection = $this->getConnection(new MySQL());
        $connection->query('SELECT * FROM tests');
        $first = $connection->getRow();
        $second = $connection->getRow();

        static::assertTrue(
            is_array($first) && is_array($second)
        );
    }

    /**
     * @test
     */
    public function insertRows($helper = false)
    {
        $connection = $this->getConnection(new MySQL());
        $count = 0;
        $success = 0;
        while ($count++ < 5) {
            $success += $connection->setQuery('INSERT INTO tests (data) VALUES (?)', rand(1000, 2000));
        }
        if (!$helper) {
            static::assertEquals(5, $success);
            return;
        }
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getRowImprovedMod()
    {
        $this->insertRows();

        /** @var MySQL $connection */
        $connection = $this->getConnection(new MODULE_DATABASE());
        $connection->query('SELECT * FROM tests');
        $first = $connection->getRow();
        $second = $connection->getRow();

        static::assertTrue(
            is_array($first) && is_array($second)
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getRowDeprecated()
    {
        if (PHP_VERSION_ID >= 70000) {
            static::markTestSkipped('Unable to perform test: Deprecated driver was removed from PHP 7.0 and above.');
            return;
        }

        $this->insertRows();
        $connection = $this->getConnection(new MySQL(), Drivers::MYSQL_DEPRECATED);
        $connection->query('SELECT * FROM tests');
        $first = $connection->getRow();
        $second = $connection->getRow();

        static::assertTrue(
            is_array($first) && is_array($second)
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getRowModDeprecated()
    {
        if (PHP_VERSION_ID >= 70000) {
            static::markTestSkipped('Unable to perform test: Deprecated driver was removed from PHP 7.0 and above.');
            return;
        }

        $this->insertRows();
        $connection = $this->getConnection(new MODULE_DATABASE(), Drivers::MYSQL_DEPRECATED);
        $connection->query('SELECT * FROM tests');
        $first = $connection->getRow();
        $second = $connection->getRow();

        static::assertTrue(
            is_array($first) && is_array($second)
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getRowPdo()
    {
        $this->insertRows();
        $connection = $this->getConnection(new MySQL(), Drivers::MYSQL_PDO);
        $connection->query('SELECT * FROM tests');
        $first = $connection->getRow();
        $second = $connection->getRow();

        static::assertTrue(
            is_array($first) && is_array($second)
        );
    }

    /**
     * @test
     */
    public function ipv6Connect()
    {
        try {
            $configured = new MySQL();
            $configured->connect(
                'manual',
                null,
                '::',
                $this->username,
                $this->password
            );

            $connection = $configured->getConnection();

            static::assertSame(get_class($connection), MySQL::class);
        } catch (Exception $e) {
            static::markTestSkipped(
                sprintf(
                    'Can not perform test %s due to %s (%d)',
                    __FUNCTION__,
                    $e->getMessage(),
                    (int)$e->getCode()
                )
            );
        }
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function ipv6ModConnect()
    {
        try {
            $configured = new MODULE_DATABASE();
            $configured->connect(
                'manual',
                null,
                '::',
                $this->username,
                $this->password
            );

            $connection = $configured->getConnection();

            static::assertSame(get_class($connection), MySQL::class);
        } catch (Exception $e) {
            static::markTestSkipped(
                sprintf(
                    'Can not perform test %s due to %s (%d)',
                    __FUNCTION__,
                    $e->getMessage(),
                    (int)$e->getCode()
                )
            );
        }
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getFirstNative()
    {
        $this->insertRows();
        /** @var MySQL $connection */
        $connection = $this->getConnection(new MySQL());
        $first = $connection->getFirst('SELECT * FROM tests LIMIT 1');
        static::assertTrue(
            isset($first['dataindex'])
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getFirstPdo()
    {
        $this->insertRows();
        /** @var MySQL $connection */
        $connection = $this->getConnection(new MySQL(), Drivers::MYSQL_PDO);
        $first = $connection->getFirst('SELECT * FROM tests LIMIT 1');
        static::assertTrue(
            isset($first['dataindex'])
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getFirstDeprecated()
    {
        if (PHP_VERSION_ID >= 70000) {
            static::markTestSkipped('Unable to perform test: Deprecated driver was removed from PHP 7.0 and above.');
            return;
        }

        $this->insertRows();
        /** @var MySQL $connection */
        $connection = $this->getConnection(new MySQL(), Drivers::MYSQL_DEPRECATED);
        $first = $connection->getFirst('SELECT * FROM tests LIMIT 1');
        static::assertTrue(
            isset($first['dataindex'])
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getQueryFirstNative()
    {
        $this->insertRows();
        /** @var MySQL $connection */
        $connection = $this->getConnection(new MySQL());
        $first = $connection->query_first('SELECT * FROM tests LIMIT 1');
        static::assertTrue(
            isset($first['dataindex'])
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getQueryFirstSafe()
    {
        $this->insertRows();
        /** @var MySQL $connection */
        $connection = $this->getConnection(new MySQL(), Drivers::MYSQL_PDO);
        //$dbRow = $connection->getResponseRow('SELECT * FROM tests LIMIT 1');
        $connection->setQuery('SELECT * FROM tests');
        $dbRow = $connection->getRow(null, null, 3);

        static::assertNotSame(
            $dbRow->getDataIndex(),
            ''
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getFirstMod()
    {
        $this->insertRows();
        /** @var MySQL $connection */
        $connection = $this->getConnection(new MODULE_DATABASE(), Drivers::MYSQL_PDO);
        $first = $connection->getFirst('SELECT * FROM tests LIMIT 1');
        static::assertTrue(
            isset($first['dataindex'])
        );
    }

    /**
     * Configurations.
     * @throws ExceptionHandler
     * @throws JsonMapper_Exception
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function getConfig()
    {
        $conf = (new DatabaseConfig())->getConfig(__DIR__ . '/config.json');
        $localhostConfigurationData = $conf->getServer('localhost');

        static::assertTrue(
            get_class($conf) === Servers::class &&
            $localhostConfigurationData->getPassword() === 'tornelib1337'
        );
    }
}
