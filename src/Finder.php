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

use BiuradPHP\Toolbox\FilePHP\Exception\FinderException;

class Finder
{
    /**
     * @var string
     */
    protected $defaultExtension = 'php';

    /**
     * @var array
     */
    protected $paths = [];

    /**
     * @var string
     */
    protected $root;

    /**
     * @var bool
     */
    protected $returnHandlers = false;

    /**
     * @var bool|null
     */
    protected $nextAsHandlers = null;

    /**
     * @param array  $path
     * @param string $defaultExtension
     * @param string $root
     */
    public function __construct(array $paths = null, $defaultExtension = null, $root = null)
    {
        if ($paths) {
            $this->addPaths((array) $paths, false);
        }

        if ($defaultExtension) {
            $this->setDefaultExtension($defaultExtension);
        }

        $this->root = $root;
    }

    /**
     * Wether to return handlers.
     *
     * @param bool $returnHandlers
     */
    public function returnHandlers($returnHandlers = true)
    {
        $this->returnHandlers = $returnHandlers;
    }

    /**
     * Wether to let the next find result return handlers.
     *
     * @param bool $returnHandlers
     */
    public function asHandlers($returnHandlers = true)
    {
        $this->nextAsHandlers = $returnHandlers;
    }

    /**
     * Wether to let the next find result return a handler.
     *
     * @param bool $returnHandler
     */
    public function asHandler($returnHandler = true)
    {
        $this->nextAsHandlers = $returnHandler;
    }

    /**
     * Returns the root.
     *
     * @return string
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Sets a root restriction.
     *
     * @param string $root
     */
    public function setRoot($root)
    {
        if (!$path = realpath($root)) {
            throw new FinderException('Location does not exist: '.$root);
        }

        $this->root = $path;
    }

    /**
     * Adds paths to look in.
     *
     * @param array $paths
     * @param bool  $clearCache
     */
    public function addPaths(array $paths, $clearCache = true, $group = '__DEFAULT__')
    {
        array_map([$this, 'addPath'], $paths, [[$clearCache, $group]]);
    }

    /**
     * Adds a path.
     *
     * @param string $path
     * @param bool   $clearCache
     */
    public function addPath($path, $clearCache = true, $group = '__DEFAULT__')
    {
        //var_dump(func_get_args());
        $path = $this->normalizePath($path);

        // This is done for easy reference and
        // eliminates the need to check for doubles
        $this->paths[$group][$path] = $path;

        if ($clearCache) {
            $this->cache = [];
        }
    }

    /**
     * Removes paths to look in.
     *
     * @param array $paths
     */
    public function removePaths(array $paths, $clearCache = true, $group = '__DEFAULT__')
    {
        array_map([$this, 'removePath'], $paths, [[$clearCache, $group]]);
    }

    /**
     * Removes a path.
     *
     * @param string $path
     */
    public function removePath($path, $clearCache = true, $group = '__DEFAULT__')
    {
        $path = $this->normalizePath($path);

        if ($path and isset($this->paths[$group][$path])) {
            unset($this->paths[$group][$path]);

            if ($clearCache) {
                $this->removePathCache($path);
            }
        }
    }

    /**
     * Removes path cache.
     *
     * @param string $path
     */
    public function removePathCache($path)
    {
        foreach ($this->cache as $key => $cache) {
            if (in_array($path, $cache['used'])) {
                unset($this->cache[$key]);
            }
        }
    }

    /**
     * Normalizes a path.
     *
     * @param string $path
     *
     * @return string
     *
     * @throws FinderException
     */
    public function normalizePath($path)
    {
        $path = rtrim($path, '/\\').DIRECTORY_SEPARATOR;
        $path = realpath($path).DIRECTORY_SEPARATOR;

        if ($this->root && strpos($path, $this->root) !== 0) {
            throw new FinderException('Cannot access path outside: '.$this->root.'. Trying to access: '.$path);
        }

        return $path;
    }

    /**
     * Returns the paths set up to look in.
     *
     * @return array
     */
    public function getPaths($group = '__DEFAULT__')
    {
        if (!isset($this->paths[$group])) {
            return [];
        }

        return array_values($this->paths[$group]);
    }

    /**
     * Retrieves the path groups.
     *
     * @return array
     */
    public function getGroups()
    {
        return array_keys($this->paths);
    }

    /**
     * Replaces all the paths.
     *
     * @param array $paths
     */
    public function setPaths(array $paths, $clearCache = true, $group = '__DEFAULT__')
    {
        $this->paths[$group] = [];
        $this->addPaths($paths, false, $group);

        if ($clearCache) {
            $this->cache = [];
        }
    }

    /**
     * Finds all files with a given name/subpath.
     *
     * @param string $name
     * @param bool   $reload
     * @param bool   $reversed
     * @param string $type
     */
    public function findAll($name, $reload = false, $reversed = false, $type = 'all')
    {
        $name = trim($name, '\\/');
        $scope = 'all::'.$type;
        $asHandlers = $this->returnHandlers;
        $group = '__DEFAULT__';
        $query = $name;

        if ($this->nextAsHandlers !== null) {
            $asHandlers = $this->nextAsHandlers;
            $this->nextAsHandlers = null;
        }

        if (strpos($query, '::') !== false) {
            list($group, $query) = explode('::', $query);
        }

        if (!isset($this->paths[$group])) {
            return [];
        }

        if ($type !== 'dir') {
            $query = $this->normalizeFileName($query);
        }

        if (!$reload and $cached = $this->findCached($scope, $name, $reversed)) {
            return $cached;
        }

        $used = [];
        $found = [];
        $paths = $reversed ? array_reverse($this->paths[$group]) : $this->paths[$group];

        foreach ($paths as $path) {
            if ($type !== 'dir' && is_file($path.$query)) {
                $found[] = $asHandlers ? new File($path.$query) : $path.$query;
                $used[] = $path;
            } elseif ($type !== 'file' && is_dir($path.$query)) {
                $found[] = $asHandlers ? new Directory($path.$query, $this->returnHandlers) : $path.$query;
                $used[] = $path;
            }
        }

        // Store the paths in cache
        $this->cache($scope, $name, $reversed, $found, $used);

        return $found;
    }

    /**
     * Finds all files with a given name/subpath.
     *
     * @param string $name
     * @param bool   $reload
     * @param bool   $reversed
     */
    public function findAllFiles($name, $reload = false, $reversed = false)
    {
        return $this->findAll($name, $reload, $reversed, 'file');
    }

    /**
     * Finds all directories with a given name/subpath.
     *
     * @param string $name
     * @param bool   $reload
     * @param bool   $reversed
     */
    public function findAllDirs($name, $reload = false, $reversed = false)
    {
        return $this->findAll($name, $reload, $reversed, 'dir');
    }

    /**
     * Reverse-finds all files and directories with a given name/subpath.
     *
     * @param string $name
     * @param bool   $reload
     * @param string $type
     */
    public function findAllReversed($name, $reload = false, $type = 'all')
    {
        return $this->findAll($name, $reload, true, $type);
    }

    /**
     * Reverse-finds all directories with a given name/subpath.
     *
     * @param string $name
     * @param bool   $reload
     */
    public function findAllDirsReversed($name, $reload = false)
    {
        return $this->findAll($name, $reload, true, 'dir');
    }

    /**
     * Reverse-finds all files with a given name/subpath.
     *
     * @param string $name
     * @param bool   $reload
     */
    public function findAllFilesReversed($name, $reload = false)
    {
        return $this->findAll($name, $reload, true, 'file');
    }

    /**
     * Finds one file or directories with a given name/subpath.
     *
     * @param string $name
     * @param bool   $reload
     * @param bool   $reversed
     * @param string $type
     */
    public function find($name, $reload = false, $reversed = false, $type = 'all')
    {
        $name = trim($name, '\\/');
        $scope = 'one::'.$type;
        $asHandlers = $this->returnHandlers;
        $query = $name;
        $group = '__DEFAULT__';

        if ($this->nextAsHandlers !== null) {
            $asHandlers = $this->nextAsHandlers;
            $this->nextAsHandlers = null;
        }

        if (strpos($query, '::') !== false) {
            list($group, $query) = explode('::', $query);
        }

        if (!isset($this->paths[$group])) {
            return;
        }

        if ($type !== 'dir') {
            $query = $this->normalizeFileName($query);
        }

        if (!$reload && $cached = $this->findCached($scope, $name, $reversed)) {
            return $cached;
        }

        $paths = $this->paths[$group];

        if ($reversed) {
            $paths = array_reverse($paths);
        }

        foreach ($paths as $path) {
            if ($type !== 'dir' && is_file($path.$query)) {
                $found = $path.$query;

                if ($asHandlers) {
                    $found = new File($found);
                }

                break;
            } elseif ($type !== 'file' && is_dir($path.$query)) {
                $found = $path.$query;

                if ($asHandlers) {
                    $found = new Directory($found);
                }

                break;
            }
        }

        if (isset($found)) {
            // Store the paths in cache
            $this->cache($scope, $name, $reversed, $found, [$path]);

            return $found;
        }
    }

    /**
     * Finds one file with a given name/subpath.
     *
     * @param string $name
     * @param bool   $reload
     * @param bool   $reversed
     */
    public function findFile($name, $reload = false, $reversed = false)
    {
        return $this->find($name, $reload, $reversed, 'file');
    }

    /**
     * Finds one directories with a given name/subpath.
     *
     * @param string $name
     * @param bool   $reload
     * @param bool   $reversed
     */
    public function findDir($name, $reload = false, $reversed = false)
    {
        return $this->find($name, $reload, $reversed, 'dir');
    }

    /**
     * Reverse-finds one file or directory with a given name/subpath.
     *
     * @param string $name
     * @param bool   $reload
     * @param bool   $reversed
     * @param string $type
     */
    public function findReversed($name, $reload = false, $type = 'all')
    {
        return $this->find($name, $reload, true, $type);
    }

    /**
     * Reverse-finds one file with a given name/subpath.
     *
     * @param string $name
     * @param bool   $reload
     * @param bool   $reversed
     */
    public function findFileReversed($name, $reload = false)
    {
        return $this->findReversed($name, $reload, 'file');
    }

    /**
     * Reverse-finds one directory with a given name/subpath.
     *
     * @param string $name
     * @param bool   $reload
     * @param bool   $reversed
     */
    public function findDirReversed($name, $reload = false)
    {
        return $this->findReversed($name, $reload, 'dir');
    }

    /**
     * Retrieves a location from cache.
     *
     * @param string $scope
     * @param string $name
     * @param bool   $reversed
     *
     * @return string|array
     */
    public function findCached($scope, $name, $reversed)
    {
        $cacheKey = $this->makeCacheKey($scope, $name, $reversed);

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey]['result'];
        }
    }

    /**
     * Clears the location cache.
     */
    public function clearCache()
    {
        $this->cached = [];
    }

    /**
     * Caches a find result.
     *
     * @param string $scope
     * @param string $name
     * @param bool   $reversed
     * @param array  $pathsUsed
     */
    public function cache($scope, $name, $reversed, $result, $pathsUsed = [])
    {
        $cacheKey = $this->makeCacheKey($scope, $name, $reversed);
        $this->cache[$cacheKey] = [
            'result' => $result,
            'used' => $pathsUsed,
        ];
    }

    /**
     * Generates a cache key.
     *
     * @param string $scope
     * @param string $name
     * @param bool   $reversed
     *
     * @return string
     */
    public function makeCacheKey($scope, $name, $reversed)
    {
        $cacheKey = $scope.'::'.$name;

        if ($reversed) {
            $cacheKey .= '::reversed';
        }

        return $cacheKey;
    }

    /**
     * Normalizes a file name.
     *
     * @param string $name
     *
     * @return string
     */
    public function normalizeFileName($name)
    {
        if (!pathinfo($name, PATHINFO_EXTENSION)) {
            $name .= '.'.$this->defaultExtension;
        }

        return $name;
    }

    /**
     * Sets the default extension.
     *
     * @param string $extension
     */
    public function setDefaultExtension($extension)
    {
        $this->defaultExtension = ltrim($extension, '.');
    }
}
