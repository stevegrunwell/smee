<?php

namespace Smee\Tests;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use Smee\Exceptions\HookExistsException;
use Smee\Exceptions\NoGitDirectoryException;
use Smee\Exceptions\NoHooksDirectoryException;
use Smee\Project;

class ProjectTest extends TestCase
{
    /**
     * @var org\bovigo\vfs\vfsStreamDirectory
     */
    protected $root;

    public function setUp()
    {
        parent::setUp();

        $this->root = vfsStream::setup('project');
    }

    public function testConstructor()
    {
        $project = new Project($this->root->url(), 'path/to/dir');
        $property = new ReflectionProperty($project, 'hooksDir');
        $property->setAccessible(true);

        $this->assertEquals($this->root->url() . '/path/to/dir', $property->getValue($project), 'Project::$hooksDir should be set.');
    }

    public function testConstructorWithDefault()
    {
        $project = new Project($this->root->url());
        $property = new ReflectionProperty($project, 'hooksDir');
        $property->setAccessible(true);

        $this->assertEquals($this->root->url() . '/.githooks', $property->getValue($project), 'Project::$hooksDir should default to .githooks.');
    }

    public function testCopyHooks()
    {
        $dir = vfsStream::create([
            '.git' => [
                'hooks' => [],
            ],
            '.githooks' => [
                'pre_commit' => 'pre_commit content',
                'post_commit' => 'post_commit content',
            ],
        ]);

        $project = new Project($this->root->url());

        $this->assertEquals(['post_commit', 'pre_commit'], $project->copyHooks());
        $this->assertTrue($dir->hasChild('.git/hooks/pre_commit'), 'The pre_commit hook should have been copied.');
        $this->assertTrue($dir->hasChild('.git/hooks/post_commit'), 'The post_commit hook should have been copied.');
    }

    public function testCopyHooksVerifiesGitDirectoryExists()
    {
        vfsStream::create([
            '.githooks' => [
                'pre_commit' => 'pre_commit content',
            ],
        ]);

        $project = new Project($this->root->url());

        $this->expectException(NoGitDirectoryException::class);
        $project->copyHooks();
    }

    public function testCopyHooksVerifiesHooksDirectoryExists()
    {
        vfsStream::create([
            '.git' => [
                'hooks' => [],
            ],
        ]);

        $project = new Project($this->root->url());

        $this->expectException(NoHooksDirectoryException::class);
        $project->copyHooks();
    }

    public function testCopyHookIfHookIsDirectory()
    {
        vfsStream::create([
            '.git' => [
                'hooks' => [],
            ],
            '.githooks' => [
                'subdirectory' => [
                    'some_file' => 'Some other file',
                ],
            ],
        ]);

        $project = new Project($this->root->url());
        $this->assertFalse($project->copyHook('subdirectory'), 'Project::copyHook() should return false if the hook is a directory.');
    }

    public function testCopyHookThrowsExceptionIfHookAlreadyExists()
    {
        vfsStream::create([
            '.git' => [
                'hooks' => [
                    'pre-commit' => 'Existing pre-commit content',
                ],
            ],
            '.githooks' => [
                'pre-commit' => 'pre-commit content',
            ],
        ]);

        $project = new Project($this->root->url());

        try {
            $project->copyHook('pre-commit');

        } catch (HookExistsException $e ) {
            $this->assertEquals('pre-commit', $e->getHook());
            return;
        }

        $this->fail('Did not receive expected HookExistsException.');
    }

    public function testGetCopiedHooks()
    {
        $instance = new Project($this->root->url());
        $hooks = [uniqid()];
        $property = new ReflectionProperty($instance, 'copied');
        $property->setAccessible(true);
        $property->setValue($instance, $hooks);

        $this->assertEquals($hooks, $instance->getCopiedHooks());
    }

    public function testStripTrailingSlashes()
    {
        $instance = new Project($this->root->url());
        $method = new ReflectionMethod($instance, 'stripTrailingSlashes');
        $method->setAccessible(true);

        $this->assertEquals('/foo/bar', $method->invoke($instance, '/foo/bar/'));
        $this->assertEquals('/foo/bar', $method->invoke($instance, '/foo/bar'));
    }
}
