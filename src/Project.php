<?php

namespace Smee;

use Composer\Script\Event;
use SebastianBergmann\Diff\Differ;
use Smee\Exceptions\HookExistsException;
use Smee\Exceptions\NoGitDirectoryException;
use Smee\Exceptions\NoHooksDirectoryException;

class Project
{
    /**
     * Contains the full system path to the base of the project.
     *
     * @var string $baseDir
     */
    protected $baseDir;

    /**
     * Contains an array of all Git hooks that have been copied.
     *
     * @var array $copied
     */
    protected $copied = [];

    /**
     * Contains the path relative to the hooks directory, relative to the project root.
     *
     * @var string $hooksDir
     */
    protected $hooksDir;

    /**
     * Contains an array of all Git hooks that have been skipped.
     *
     * @var array $skipped
     */
    protected $skipped = [];

    /**
     * Instantiate a new project with Smee.
     *
     * @param string $hooksDir Optional. The hooks directory, relative to the project root. Default
     *                         is the .githooks directory.
     * @param string $baseDir  The full system path to the root of the project directory.
     */
    public function __construct($baseDir, $hooksDir = '.githooks')
    {
        $this->baseDir = $this->stripTrailingSlashes($baseDir);
        $this->hooksDir = $this->baseDir . '/' . $this->stripTrailingSlashes($hooksDir);
    }

    /**
     * Copy the hooks from the hooks directory into the local git repository.
     *
     * @throws NoGitDirectoryException   When there is no .git directory.
     * @throws NoHooksDirectoryException When the git hooks directory is missing.
     *
     * @return array An array containing the filenames of all copied hooks.
     */
    public function copyHooks()
    {
        // Throw Exceptions if either the .git or hooks directories are missing.
        if (! is_dir($this->baseDir . '/.git')) {
            throw new NoGitDirectoryException(sprintf('No .git directory was found within %s.', $this->baseDir));
        } elseif (! is_dir($this->hooksDir) || ! is_readable($this->hooksDir)) {
            throw new NoHooksDirectoryException(
                sprintf('The git hooks directory at %s is inaccessible.', $this->hooksDir)
            );
        }

        // Read the contents of $this->hooksDir and copy them.
        $contents = scandir($this->hooksDir);

        array_map([$this, 'copyHook'], $contents);

        return $this->copied;
    }

    /**
     * Copy a single hook from the local directory to .git/hooks.
     *
     * @throws HookExistsException If the target hook already exists.
     *
     * @param string $hook  The name of the hook to copy from $this->hooksDir.
     * @param bool   $force Force overwrite of existing hooks. Default is false.
     *
     * @return bool True if the hook was copied, false if it was ineligible to be copied.
     */
    public function copyHook($hook, $force = false)
    {
        $hook = basename($hook);
        $path = $this->hooksDir . '/' . $hook;
        $dest = $this->baseDir . '/.git/hooks/' . $hook;

        if (in_array($hook, $this->skipped, true) || is_dir($path)) {
            return false;
        }

        if (file_exists($dest) && ! $force) {

            // The file exists, but it's the same as what we're about to copy.
            if (md5_file($dest) === md5_file($path)) {
                $this->skipHook($hook);
                return false;
            }

            $exception = new HookExistsException(sprintf('A %s hook already exists for this repository!', $hook));
            $exception->hook = $hook;

            throw $exception;
        }

        if (copy($path, $dest)) {
            $this->copied[] = $hook;
            return true;
        }

        return false;
    }

    /**
     * Generate a diff based on the hook name and the project configuration.
     *
     * @param string $hook The name of the hook file, which should exist in both .git/hooks and
     *                     $this->hooksDir.
     *
     * @return string A diff of the two files.
     */
    public function diffHook($hook)
    {
        $file   = $this->hooksDir . '/' . $hook;
        $target = $this->baseDir . '/.git/hooks/' . $hook;

        return $this->diffHooks($file, $target);
    }

    /**
     * Generate a diff between two hook files.
     *
     * @param string $file   The filepath to the new hook file.
     * @param string $target The filepath to the existing hook file.
     *
     * @return string A human-readable diff.
     */
    public function diffHooks($file, $target)
    {
        $differ = new Differ;

        return $differ->diff(file_get_contents($target), file_get_contents($file));
    }

    /**
     * Retrieve an array of copied hooks.
     *
     * @return array An array of copied hook names.
     */
    public function getCopiedHooks()
    {
        return (array) $this->copied;
    }

    /**
     * Retrieve an array of skipped hooks.
     *
     * @return array An array of skipped hook names.
     */
    public function getSkippedHooks()
    {
        return (array) $this->skipped;
    }

    /**
     * Mark a hook to be skipped.
     *
     * @param string $hook The hook name to skip.
     */
    public function skipHook($hook)
    {
        $this->skipped[] = $hook;
    }

    /**
     * Remove trailing slashes from a directory name.
     *
     * @param string $path The path or URL to strip trailing slashes from.
     *
     * @return string The $path, devoid of trailing slashes.
     */
    protected function stripTrailingSlashes($path)
    {
        return '/' === substr($path, -1, 1) ? substr($path, 0, -1) : $path;
    }
}
