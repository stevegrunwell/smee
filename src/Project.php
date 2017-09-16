<?php

namespace Smee;

use Composer\Script\Event;
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
        $copied = [];

        // Throw Exceptions if either the .git or hooks directories are missing.
        if (! is_dir($this->baseDir . '/.git')) {
            throw new NoGitDirectoryException(sprintf('No .git directory was found within %s.', $this->baseDir));

        } elseif (! is_dir($this->hooksDir) || ! is_readable($this->hooksDir)) {
            throw new NoHooksDirectoryException(sprintf('The git hooks directory at %s is inaccessible.', $this->hooksDir));
        }

        $contents = scandir($this->hooksDir);
        $dest = $this->baseDir . '/.git/hooks/';

        foreach ($contents as $file) {
            $path = $this->hooksDir . '/' . $file;

            if (is_dir($path)) {
                continue;
            }

            if (copy($path, $dest . basename($file))) {
                $copied[] = $file;
            }
        }

        return $copied;
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
