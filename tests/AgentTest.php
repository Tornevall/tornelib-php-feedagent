<?php

namespace TorneLIB\Feed;

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;

class AgentTest extends TestCase
{
    private $Agent;

    /**
     * Default setup.
     */
    public function setUp(): void
    {
        $this->Agent = new Agent();
    }

    /**
     * @testdox Regular class check.
     */
    public function testMainAgent(): void
    {
        self::assertTrue(get_class(new Agent()) === Agent::class);
    }

    /**
     * @testdox Test that the storage engine returns anything.
     */
    public function testStorageAgent(): void
    {
        self::assertTrue((strlen($this->Agent->getStorageEngine()) > 3));
    }
}
