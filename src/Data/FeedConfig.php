<?php

namespace TorneLIB\Data;

/**
 * Class FeedConfig
 * @package TorneLIB\Data
 */
class FeedConfig
{
    /**
     * @var string
     * @since 1.0.0
     */
    public $storage;

    /**
     * @param string $storage
     * @since 1.0.0
     */
    public function setStorage($storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @since 1.0.0
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
