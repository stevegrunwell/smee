<?php

namespace Smee\Tests\Console;

use org\bovigo\vfs\vfsStream;
use ReflectionClass;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

trait CommandTestTrait
{
    /**
     * @var Symfony\Component\Console\Application
     */
    protected $app;

    /**
     * @var Smee\Console\InstallCommand
     */
    protected $command;

    /**
     * @var Symfony\Component\Console\Tester\CommandTester
     */
    protected $commandTester;

    /**
     * @var org\bovigo\vfs\vfsStreamDirectory
     */
    protected $root;

    public function setUp()
    {
        parent::setUp();

        $this->root = vfsStream::setup('project');

        // Create a shared instance of the application, along with a reference to the command.
        $this->command = new $this->commandClass;

        $this->app = new Application;
        $this->app->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Skip tests if the suite doesn't define a $commandClass property.
     */
    public static function setUpBeforeClass()
    {
        $class = new ReflectionClass(__CLASS__);

        if (! $class->hasProperty('commandClass')) {
            // @codingStandardsIgnoreLine
            self::markTestSkipped('Test suite is using the CommandTestTrait, but does not declare a $commandClass property.');
        }
    }
}
