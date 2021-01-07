<?php

namespace TorneLIB\Model\Database;

/**
 * Class ServerData
 * @package TorneLIB\Model\Database
 * @since 6.1.0
 */
class ServerData
{
    /**
     * @var string $user Username.
     * @since 6.1.0
     */
    private $user;

    /**
     * @var string $server Hostname or ip.
     * @since 6.1.0
     */
    private $server;

    /**
     * @var string $password
     * @since 6.1.0
     */
    private $password;

    /**
     * @var string $schema Database/schema name.
     * @since 6.1.0
     */
    private $schema;

    /**
     * @return mixed
     * @since 6.1.0
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     * @since 6.1.0
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     * @since 6.1.0
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @param mixed $schema
     * @since 6.1.0
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;
    }

    /**
     * @return mixed
     * @since 6.1.0
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param mixed $server
     * @since 6.1.0
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * @return mixed
     * @since 6.1.0
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     * @since 6.1.0
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Magic function set to forget anything not predefined by class.
     * @param $name
     * @param $arguments
     * @return null
     */
    public function __call($name, $arguments)
    {
        return null;
    }
}
