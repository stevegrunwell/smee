<?php

namespace Smee;

use Composer\Script\Event;
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
            throw new NoHooksDirectoryException(sprintf('The git hooks directory at %s is inaccessible.', $this->hooksDir));
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

        if (is_dir($path)) {
            return false;
        }

        if (file_exists($dest) && ! $force) {

            // The file exists, but it's the same as what we're about to copy.
            if (md5_file($dest) === md5_file($path)) {
                return false;
            }

            $exception = new HookExistsException(sprintf('A %s hook already exists for this repository!', $hook));
            $exception->hook = $hook;

            throw $exception;
        }

        if (copy($path, $dest)) {
            $this->copied[] = $hook;
        }
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
