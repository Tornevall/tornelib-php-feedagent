<?php

namespace TorneLIB\Feed;

use JsonMapper;
use JsonMapper_Exception;
use TorneLIB\Data\FeedConfig;

/**
 * Class Agent
 * @package TorneLIB\Feed
 */
class Agent
{
    private JsonMapper $JSON;
    private FeedConfig $feedConfig;
    private $confPath = __DIR__ . '/../../config.json';

    /**
     * Agent constructor.
     * @throws JsonMapper_Exception
     */
    public function __construct()
    {
        $this->JSON = (new JsonMapper());
        $this->getInitializedConfig();
    }

    /**
     * @throws JsonMapper_Exception
     */
    private function getInitializedConfig()
    {
        if (file_exists($this->confPath)) {
            $config = json_decode(file_get_contents($this->confPath));
            if (is_object($config)) {
                $this->feedConfig = $this->JSON->map(
                    $config,
                    new FeedConfig()
                );
            }
        }

        return $this;
    }

    /**
     * @since 1.0.0
     */
    public function getStorageEngine()
    {
        return $this->feedConfig->getStorage();
    }
}
