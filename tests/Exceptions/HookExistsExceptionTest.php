<?php

namespace Smee\Tests;

use PHPUnit\Framework\TestCase;
use Smee\Exceptions\HookExistsException;

class HookExistsExceptionTest extends TestCase
{
    public function testCanRetrieveHook()
    {
        $exception = new HookExistsException('Test message');
        $exception->hook = uniqid();

        $this->assertEquals($exception->hook, $exception->getHook());
    }
}
