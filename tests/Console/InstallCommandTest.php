<?php

namespace Smee\Tests\Console;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Smee\Console\InstallCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class InstallCommandTest extends TestCase
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

        /*
         * Create a shared instance of the application, along with a reference to the
         * 'smee:install' command.
         */
        $this->app = new Application;
        $this->app->add(new InstallCommand);

        $this->command = $this->app->find('smee:install');
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecute()
    {
        vfsStream::create([
            '.git' => [
                'hooks' => [],
            ],
            '.githooks' => [
                'pre_commit' => 'pre_commit content',
                'post_commit' => 'post_commit content',
            ],
        ]);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--base-dir' => $this->root->url(),
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertContains('pre_commit', $output, 'Expected to see "pre_commit" listed in the command output.');
        $this->assertContains('post_commit', $output, 'Expected to see "post_commit" listed in the command output.');
    }

    public function testExecuteOnlyShowsSuccessMessageIfHooksWereCopied()
    {
        vfsStream::create([
            '.git' => [
                'hooks' => [],
            ],
            '.githooks' => [],
        ]);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--base-dir' => $this->root->url(),
        ]);

        $this->assertEmpty($this->commandTester->getDisplay());
    }

    public function testExecuteHandlesCustomHooksDirectory()
    {
        vfsStream::create([
            '.git' => [
                'hooks' => [],
            ],
            'my-githooks-directory' => [
                'pre_commit' => 'pre_commit content',
            ],
        ]);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--base-dir' => $this->root->url(),
            '--hooks' => 'my-githooks-directory',
        ]);

        $this->assertContains('pre_commit', $this->commandTester->getDisplay());
    }

    public function testExecuteHandlesMissingGitDirectory()
    {
        vfsStream::create([
            '.githooks' => [
                'pre_commit' => 'pre_commit content',
            ],
        ]);

        $this->assertEquals(1, $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--base-dir' => $this->root->url(),
        ]), 'Command failure should result in an exit code of 1.');
    }

    public function testExecuteHandlesMissingHooksDirectory()
    {
        vfsStream::create([
            '.git' => [
                'hooks' => [],
            ],
        ]);

        $this->assertEquals(1, $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--base-dir' => $this->root->url(),
        ]), 'Command failure should result in an exit code of 1.');
    }
}
