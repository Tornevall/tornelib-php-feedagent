<?php

namespace TorneLIB\Module\Database\Drivers;

/**
 * Class DataResponse
 * @package TorneLIB\Module\Database\Drivers
 * @since 6.1.0
 */
class DataResponseRow
{
    private $response;

    /**
     * @param $keyArray
     * @param $value
     * @return DataResponseRow
     */
    public function setResponse($keyArray, $value = null)
    {
        if (!is_array($keyArray) && !is_object($keyArray)) {
            $this->response[strtolower($keyArray)] = $value;
        } else {
            $keys = array_keys((array)$keyArray);

            foreach ($keys as $key) {
                $this->setResponse($key, $keyArray[$key]);
            }
        }

        return $this;
    }

    public function __call($name, $arguments)
    {
        $return = null;
        $gettable = strtolower(substr($name, 3));

        if (isset($this->response[$gettable])) {
            $return = $this->response[$gettable];
        }

        return $return;
    }
}
