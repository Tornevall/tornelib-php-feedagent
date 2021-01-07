<?php

namespace TorneLIB\Feed;

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;

class AgentTest extends TestCase
{
    private $Agent;

    public function setUp(): void
    {
        $this->Agent = new Agent();
    }

    public function testMainAgent(): void
    {
        self::assertTrue(get_class(new Agent()) === Agent::class);
    }

    public function testStorageAgent(): void
    {
        self::assertTrue((strlen($this->Agent->getStorageEngine()) > 3));
    }
}
