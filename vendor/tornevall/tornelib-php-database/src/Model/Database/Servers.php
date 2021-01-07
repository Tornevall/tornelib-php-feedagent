<?php

namespace TorneLIB\Model\Database;

/**
 * Class Servers
 * @package TorneLIB\Model\Database
 * @since 6.1.0
 */
class Servers
{
    /**
     * @var ServerData $servers Serverlist.
     * @since 6.1.0
     */
    private $servers = [];

    /**
     * @return array
     * @since 6.1.0
     */
    public function getServers()
    {
        return $this->servers;
    }

    /**
     * @param array $serverArray
     * @return Servers
     * @since 6.1.0
     */
    public function setServers($serverArray = [])
    {
        foreach ($serverArray as $serverKey => $serverVariables) {
            $serverData = new ServerData();
            foreach ($serverVariables as $key => $value) {
                $serverData->{sprintf('set%s', ucfirst($key))}($value);
            }
            $this->servers[$serverKey] = $serverData;
        }

        return $this;
    }

    /**
     * @param null $identifier
     * @return mixed|null
     * @since 6.1.0
     */
    public function getServer($identifier = null)
    {
        if (is_null($identifier)) {
            $identifier = 'localhost';
        }
        return isset($this->servers[$identifier]) ? $this->servers[$identifier] : null;
    }
}
