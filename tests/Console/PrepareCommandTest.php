<?php

namespace Smee\Tests\Console;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Smee\Console\PrepareCommand;
use Smee\Tests\Console\CommandTestTrait;

class PrepareCommandTest extends TestCase
{
    use CommandTestTrait;

    /**
     * A reference to the class name that powers the command being tested.
     *
     * @var string $commandClass
     */
    protected $commandClass = PrepareCommand::class;

    public function testVerifiesGitDirectory()
    {
        $dir = vfsStream::create([
            '.git' => [],
        ]);

        $this->assertEquals(0, $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--base-dir' => $this->root->url(),
        ]));

        $this->assertContains('...OK', $this->commandTester->getDisplay());
    }

    public function testCanCreateGitDirectory()
    {
        $dir = vfsStream::create([]);

        $this->commandTester->setInputs(['yes']);

        $this->assertFalse($dir->hasChild('.git'));
        $this->assertEquals(0, $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--base-dir' => $this->root->url(),
        ]));
    }

    public function testAbortsIfGitDirectoryWasNotRequested()
    {
        $dir = vfsStream::create([]);

        $this->commandTester->setInputs(['n']);

        $this->assertFalse($dir->hasChild('.git'));

        $this->assertEquals(1, $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--base-dir' => $this->root->url(),
        ]), 'Command failure should result in an exit code of 1.');
    }
}
