<?php

namespace TorneLIB\Feed;

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;

class AgentTest extends TestCase
{
    public function testMainAgent()
    {
        self::assertTrue(get_class(new Agent()) === Agent::class);
    }
}
