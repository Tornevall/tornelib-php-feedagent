<?php

namespace TorneLIB\Model\Database;

class Configuration
{
    /**
     * @var Servers
     * @since 6.1.0
     */
    public $database;

    /**
     * @param Servers $database
     * @since 6.1.0
     */
    public function setDatabase($database)
    {
        $this->database = $database;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @since 6.1.0
     */
    public function __call($name, $arguments)
    {
        $return = null;
        $variableName = lcfirst(substr($name, 3));

        if (isset($this->$variableName) && (0 === strpos($name, "get"))) {
            $return = $this->$variableName;
        }

        return $return;
    }
}
