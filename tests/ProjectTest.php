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

        $this->assertEquals(
            $this->root->url() . '/path/to/dir',
            $property->getValue($project),
            'Project::$hooksDir should be set.'
        );
    }

    public function testConstructorWithDefault()
    {
        $project = new Project($this->root->url());
        $property = new ReflectionProperty($project, 'hooksDir');
        $property->setAccessible(true);

        $this->assertEquals(
            $this->root->url() . '/.githooks',
            $property->getValue($project),
            'Project::$hooksDir should default to .githooks.'
        );
    }

    public function testCopyHooks()
    {
        $dir = vfsStream::create([
            '.git' => [
                'hooks' => [],
            ],
            '.githooks' => [
                'pre-commit' => 'pre-commit content',
                'post-commit' => 'post-commit content',
            ],
        ]);

        $project = new Project($this->root->url());

        $this->assertEquals(['post-commit', 'pre-commit'], $project->copyHooks());
        $this->assertTrue($dir->hasChild('.git/hooks/pre-commit'), 'The pre-commit hook should have been copied.');
        $this->assertTrue($dir->hasChild('.git/hooks/post-commit'), 'The post-commit hook should have been copied.');
    }

    public function testCopyHooksVerifiesGitDirectoryExists()
    {
        vfsStream::create([
            '.githooks' => [
                'pre-commit' => 'pre-commit content',
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

    public function testCopyHooksIgnoresHooksInSkippedArray()
    {
        vfsStream::create([
            '.git' => [
                'hooks' => [],
            ],
            '.githooks' => [
                'pre-commit' => 'pre-commit hook',
            ],
        ]);

        $project = new Project($this->root->url());
        $project->skipHook('pre-commit');

        $this->assertFalse($project->copyHook('pre-commit'));
        $this->assertEmpty($project->getCopiedHooks());
    }

    public function testCopyHook()
    {
        vfsStream::create([
            '.git' => [
                'hooks' => [],
            ],
            '.githooks' => [
                'pre-commit' => 'pre-commit hook',
            ],
        ]);

        $project = new Project($this->root->url());

        $this->assertTrue(
            $project->copyHook('pre-commit'),
            'Project::copyHook() should return a boolean TRUE if copy was successful.'
        );
        $this->assertEquals(
            'pre-commit hook',
            file_get_contents($this->root->url() . '/.git/hooks/pre-commit'),
            'The contents of pre-commit should have been copied to .git/hooks/pre-commit.'
        );
        $this->assertTrue(
            is_executable($this->root->url() . '/.git/hooks/pre-commit'),
            'Project::copyHook() should ensure git hooks are executable.'
        );
    }

    public function testCopyHookReturnsFalseIfCopyFails()
    {
        $dir = vfsStream::create([
            '.git' => [
                'hooks' => [],
            ],
            '.githooks' => [
                'pre-commit' => 'pre-commit hook',
            ],
        ]);
        $dir->getChild('.git')->getChild('hooks')->chmod(0444);

        $project = new Project($this->root->url());

        $this->assertFalse(
            $project->copyHook('pre-commit'),
            'The hook should not have been able to be copied, due to file permissions.'
        );
    }

    public function testCopyHookIfHookIsDirectory()
    {
        vfsStream::create([
            '.git' => [
                'hooks' => [],
            ],
            '.githooks' => [
                'subdirectory' => [
                    'some-file' => 'Some other file',
                ],
                'pre-commit' => 'pre-commit content',
            ],
        ]);

        $project = new Project($this->root->url());

        $this->assertFalse(
            $project->copyHook('subdirectory'),
            'Project::copyHook() should return false if the hook is a directory.'
        );
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
        } catch (HookExistsException $e) {
            $this->assertEquals('pre-commit', $e->getHook());
            return;
        }

        $this->fail('Did not receive expected HookExistsException.');
    }

    public function testCopyHookIgnoresExistingHooksThatMatchWhatIsBeingCopied()
    {
        vfsStream::create([
            '.git' => [
                'hooks' => [
                    'pre-commit' => 'pre-commit content',
                ],
            ],
            '.githooks' => [
                'pre-commit' => 'pre-commit content',
            ],
        ]);

        $project = new Project($this->root->url());
        $project->copyHook('pre-commit');

        $this->assertEmpty($project->getCopiedHooks());
        $this->assertContains(
            'pre-commit',
            $project->getSkippedHooks(),
            'The pre-commit hook should be added to Project::$skipped.'
        );
    }

    public function testCopyHookCanForceOverwrite()
    {
        vfsStream::create([
            '.git' => [
                'hooks' => [
                    'pre-commit' => 'old pre-commit content',
                ],
            ],
            '.githooks' => [
                'pre-commit' => 'new pre-commit content',
            ],
        ]);

        $project = new Project($this->root->url());

        $project->copyHook('pre-commit', true);

        $this->assertContains('pre-commit', $project->getCopiedHooks());
        $this->assertEquals(
            'new pre-commit content',
            file_get_contents($this->root->url() . '/.git/hooks/pre-commit'),
            'If forced, the target hook should be overwritten.'
        );
    }

    public function testDiffHook()
    {
        vfsStream::create([
            '.git' => [
                'hooks' => [
                    'pre-commit' => 'old pre-commit content',
                ],
            ],
            '.githooks' => [
                'pre-commit' => 'new pre-commit content',
            ],
        ]);

        $project = new Project($this->root->url());
        $diff = <<<EOT
-old pre-commit content
+new pre-commit content
EOT;

        $this->assertContains($diff, $project->diffHook('pre-commit'));
    }

    public function testDiffHooks()
    {
        vfsStream::create([
            '.git' => [
                'hooks' => [
                    'pre-commit' => 'old pre-commit content',
                ],
            ],
            '.githooks' => [
                'pre-commit' => 'new pre-commit content',
            ],
        ]);

        $project = new Project($this->root->url());
        $diff = <<<EOT
-old pre-commit content
+new pre-commit content
EOT;

        $this->assertContains(
            $diff,
            $project->diffHooks(
                $this->root->url() . '/.githooks/pre-commit',
                $this->root->url() . '/.git/hooks/pre-commit'
            )
        );
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

    public function testGetSkippedHooks()
    {
        $instance = new Project($this->root->url());
        $hooks = [uniqid()];
        $property = new ReflectionProperty($instance, 'skipped');
        $property->setAccessible(true);
        $property->setValue($instance, $hooks);

        $this->assertEquals($hooks, $instance->getSkippedHooks());
    }

    public function testSkipHook()
    {
        $instance = new Project($this->root->url());
        $hook = uniqid();
        $property = new ReflectionProperty($instance, 'skipped');
        $property->setAccessible(true);

        $instance->skipHook($hook);

        $this->assertContains($hook, $property->getValue($instance));
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
