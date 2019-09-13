<?php

/*
 * The Biurad Toolbox FileHandler.
 *
 * This file is used to get rid of some discouraged
 * funtion usuage when it comes to php filesystem.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 * @license MIT
 */
declare(strict_types=1);

namespace BiuradPHP\Toolbox\FilePHP;

use BiuradPHP\Toolbox\FilePHP\Exception\FileException;

class Directory extends Handler
{
    /**
     * Deletes a directory recursively.
     *
     * @return bool
     */
    public function deleteRecursive()
    {
        return $this->delete(true);
    }

    /**
     * Deletes a directory.
     *
     * @param bool $recursive
     *
     * @return bool
     */
    public function delete($recursive = false)
    {
        if (!$recursive) {
            return parent::delete();
        }

        $finder = new Finder();
        $contents = $finder->listContents($this->path);

        foreach ($contents as $item) {
            $item->delete(true);
        }

        return parent::delete();
    }

    /**
     * Lists all files in a directory.
     *
     * @param int   $depth
     * @param mixed $filter
     * @param bool  $asHandlers
     *
     * @return array
     */
    public function listFiles($depth = 0, $filter = null, $asHandlers = false)
    {
        return $this->listContents($depth, $filter, 'file', $asHandlers);
    }

    /**
     * Lists all files in a directory as Handlers.
     *
     * @param int   $depth
     * @param mixed $filter
     *
     * @return array
     */
    public function listFileHandlers($depth = 0, $filter = null)
    {
        return $this->listContents($depth, $filter, 'file', true);
    }

    /**
     * Lists all directories in a directory.
     *
     * @param int   $depth
     * @param mixed $filter
     * @param bool  $asHandlers
     *
     * @return array
     */
    public function listDirs($depth = 0, $filter = null, $asHandlers = false)
    {
        return $this->listContents($depth, $filter, 'dir', $asHandlers);
    }

    /**
     * Lists all directories in a directory.
     *
     * @param int   $depth
     * @param mixed $filter
     *
     * @return array
     */
    public function listDirHandlers($depth = 0, $filter = null)
    {
        return $this->listContents($depth, $filter, 'dir', true);
    }

    /**
     * Attempts to create the directory specified by pathname.
     *
     * @param string $dir
     *
     * @return bool
     */
    public function makeDir()
    {
        $dir = $this->path;
        if ($dir !== null) {
            $this->path = $this->hide->call('dirname', $dir);
        }

        // Silence error for open_basedir; should fail in mkdir instead.
        if (@$this->is_dir()) {
            return true;
        }

        $success = $this->hide->call('mkdir', $this->path, 0777, true);

        if (!$success) {
            // Take yet another look, make sure that the folder doesn't exist.
            $this->hide->call('clearstatcache', true, $this->path);

            if (!@$this->hide->call('is_dir', $this->path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Removes a directory (and its contents) recursively.
     *
     * @param bool   $traverseSymlinks Delete contents of symlinks recursively
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function removeDir($traverseSymlinks = false): bool
    {
        $dir = $this->path;

        if (!$this->exists()) {
            return true;
        }

        if (!$this->is_dir()) {
            throw new \RuntimeException('Given path is not a directory');
        }

        if ($traverseSymlinks || !$this->hide->call('is_link', $this->path)) {
            foreach ($this->scan_dir() as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $currentPath = $this->path.'/'.$file;

                if ($this->hide->call('is_dir', $currentPath)) {
                    $this->removeDir($currentPath, $traverseSymlinks);
                } elseif (!$this->hide->call('unlink', $currentPath)) {
                    // @codeCoverageIgnoreStart
                    throw new \RuntimeException(sprintf('Unable to delete %s', $currentPath));
                    // @codeCoverageIgnoreEnd
                }
            }
        }

        // @codeCoverageIgnoreStart
        // Windows treats removing directory symlinks identically to removing directories.
        if (!defined('PHP_WINDOWS_VERSION_MAJOR') && $this->hide->call('is_link', $this->path)) {
            if (!$this->delete()) {
                throw new \RuntimeException(sprintf('Unable to delete %s', $dir));
            }
        } else {
            if (!$this->hide->call('rmdir', $this->path)) {
                throw new \RuntimeException(sprintf('Unable to delete %s', $dir));
            }
        }

        return true;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Creates a temporary directory.
     *
     * @param string $namespace the directory path in the isseted path
     * @param string $className the name of the test class
     *
     * @return string the path to the created directory
     */
    public function makeTmpDir(string $namespace, string $className): string
    {
        if (false !== ($pos = strrpos($className, '\\'))) {
            $shortClass = substr($className, $pos + 1);
        } else {
            $shortClass = $className;
        }

        // Usage of realpath() is important if the temporary directory is a
        // symlink to another directory (e.g. /var => /private/var on some Macs)
        // We want to know the real path to avoid comparison failures with
        // code that uses real paths only
        $systemTempDir = str_replace('\\', '/', $this->hide->call('realpath', $this->path));
        $basePath = $systemTempDir.'/'.$namespace.'/'.$shortClass;

        $result = false;
        $attempts = 0;

        do {
            $tmpDir = str_replace('/', DIRECTORY_SEPARATOR, $basePath.random_int(10000, 99999));

            try {
                $this->hide->call('mkdir', $tmpDir, 0777);

                $result = true;
            } catch (FileException $exception) {
                ++$attempts;
            }
        } while (false === $result && $attempts <= 10);

        return $tmpDir;
    }

    /**
     * Lists all files and directories in a directory.
     *
     * @param int    $depth
     * @param mixed  $filter
     * @param string $type
     * @param bool   $asHandlers
     *
     * @return array
     */
    public function listContents($depth = 0, $filter = null, $type = 'all', $asHandlers = false)
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        $pattern = $this->path.'/*';

        if (is_array($filter)) {
            $filters = $filter;
            $filter = new Filter();

            foreach ($filters as $f => $type) {
                if (!is_int($f)) {
                    $f = $type;
                    $type = null;
                }

                $expected = true;

                if (strpos($f, '!') === 0) {
                    $f = substr($f, 1);
                    $expected = false;
                }

                $filter->addFilter($f, $expected, $type);
            }
        }

        if ($filter instanceof \Closure) {
            $callback = $filter;
            $filter = new Filter();
            $callback($filter);
        }

        if (!$filter) {
            $filter = new Filter();
        }

        $flags = GLOB_MARK;

        if ($type === 'file' and !pathinfo($pattern, PATHINFO_EXTENSION)) {
            // Add an extension wildcard
            $pattern .= '.*';
        } elseif ($type === 'dir') {
            $flags = GLOB_MARK | GLOB_ONLYDIR;
        }

        $contents = glob($pattern, $flags);

        // Filter the content.
        $contents = $filter->filter($contents);

        // Lower the depth for a recursive call
        if ($depth and $depth !== true) {
            --$depth;
        }

        $formatted = array();

        foreach ($contents as $item) {
            if ($filter->isCorrectType('dir', $item)) {
                $_contents = array();

                if (($depth === true or $depth === 0) and !$asHandlers) {
                    $dir = new Directory($item);

                    $_contents = $dir->listContents($item, $filter, $depth, $type);
                }

                if ($asHandlers) {
                    $formatted[] = new Directory($item);
                } else {
                    $formatted[$item] = $_contents;
                }
            } elseif ($filter->isCorrectType('file', $item)) {
                if ($asHandlers) {
                    $item = new File($item);
                }

                $formatted[] = $item;
            }
        }

        return $formatted;
    }
}
