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

abstract class Handler
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var Silencer
     */
    protected $hide;

    /**
     * @var resource
     */
    protected $handle;

    /**
     * @var bool|null
     */
    protected $locked;

    /**
     * @param string $path
     */
    public function __construct($path = null)
    {
        $this->hide = new Silencer();
        $this->path = $this->toIterable($path);
    }

    /**
     * Get file instance.
     *
     * @param string $path
     *
     * @return static
     */
    public static function getInstance($path = null)
    {
        if (!\is_string($path)) {
            throw new \InvalidArgumentException('Given path should be non-empty string');
        }

        return new static($path);
    }

    /**
     * Unlock file when the object gets destroyed.
     */
    public function __destruct()
    {
        if ($this->locked()) {
            return $this->unlock();
        }
    }

    /**
     * Interface to detect if a class is traversable using foreach, array or string.
     *
     * @param string|array $files
     *
     * @return iterable
     */
    public function toIterable($files): iterable
    {
        return \is_array($files) || $files instanceof \Traversable ? $files : [$files];
    }

    /**
     * Checks whether a file/dir exists.
     *
     * @return bool
     */
    public function exists()
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        return $this->hide->call('file_exists', $this->path);
    }

    /**
     * Deletes a file/dir.
     *
     * @return bool
     */
    public function delete()
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        return $this->hide->call('unlink', $this->path);
    }

    /**
     * Moves a file/dir.
     *
     * @return bool
     */
    public function moveTo($destination)
    {
        return $this->renameTo($destination);
    }

    /**
     * Renames a file/dir.
     *
     * @return bool
     */
    public function renameTo($name)
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        if (strpos($name, DIRECTORY_SEPARATOR) !== 0) {
            $name = $this->hide->call('pathinfo', $this->path, PATHINFO_DIRNAME).
                DIRECTORY_SEPARATOR.$name;
        }

        if (!$this->hide->call('pathinfo', $name, PATHINFO_EXTENSION)) {
            $name .= '.'.$this->hide->call('pathinfo', $this->path, PATHINFO_EXTENSION);
        }

        if ($result = $this->hide->call('rename', $this->path, $name)) {
            $this->path = $this->hide->call('realpath', $name);
        }

        return $result;
    }

    /**
     * Creates a symlink to a file/dir.
     *
     * @return bool
     */
    public function symlinkTo($destination)
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        return $this->hide->call('symlink', $this->path, $destination);
    }

    /**
     * Checks wether a file/dir is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        return $this->hide->call('is_writable', $this->path);
    }

    /**
     * Checks wether a file/dir is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        return $this->hide->call('is_readable', $this->path);
    }

    /**
     * Retrieves wether the path is a file or a dir.
     *
     * @return string
     */
    public function getType()
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        return $this->hide->call('filetype', $this->path);
    }

    /**
     * Retrieves the last access time.
     *
     * @return int
     */
    public function getAccessTime()
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        return $this->hide->call('fileatime', $this->path);
    }

    /**
     * Retrieves the last modified time.
     *
     * @return int
     */
    public function getModifiedTime()
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        return $this->hide->call('filemtime', $this->path);
    }

    /**
     * Returns Path info of the file.
     *
     * @param string $path
     * @param int    $options
     */
    public function getPathInfo(int $options = null)
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        if (null !== $options) {
            return $this->hide->call('pathinfo', $this->path, $options);
        }

        return $this->hide->call('pathinfo', $this->path, PATHINFO_FILENAME);
    }

    /**
     * Retrieves the created time.
     *
     * @return int
     */
    public function getCreatedTime()
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        return $this->hide->call('filectime', $this->path);
    }

    /**
     * Retrieves the permissions.
     *
     * @return int
     */
    public function getPermissions()
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        return $this->hide->call('fileperms', $this->path);
    }

    /**
     * Sets the permissions.
     *
     * @return bool
     */
    public function setPermissions($permissions)
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        if (is_string($permissions)) {
            $permissions = '0'.ltrim($permissions, '0');
            $permissions = octdec($permissions);
        }

        return $this->hide->call('chmod', $this->path, $permissions);
    }

    /**
     * Excute a command or file to both shell or web.
     *
     * @param bool $file
     *
     * @return bool|int|void
     */
    public function passThru(bool $file = true)
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        $handle = $this->hide->call('fopen', $this->path, 'r');

        $contents = $file ? $this->hide->call('fpassthru', $handle) :
            $this->hide->call('passthru', $this->path);

        $this->hide->call('fclose', $handle);

        return $contents;
    }

    /**
     * Returns the number of files in a given directory.
     *
     * @param array  $exclude
     * @param string $fileExtension
     *
     * @return int
     */
    public function count($fileExtension = '')
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        $search = !empty($fileExtension) ? '*'.$fileExtension : '*';

        return count($this->hide->call('glob', $this->path.$search));
    }

    /**
     * Function to strip additional / or \ in a path name.
     *
     * @param string $dirSep directory separator (optional)
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function clean($dirSep = DIRECTORY_SEPARATOR): string
    {
        $path = $this->path;
        if (!is_string($path) || empty($path)) {
            return '';
        }

        $path = trim((string) $path);

        if (empty($path)) {
            $path = @$_SERVER['DOCUMENT_ROOT'] ?: '';
        } elseif (($dirSep === '\\') && ($path[0] === '\\') && ($path[1] === '\\')) {
            $path = '\\'.preg_replace('#[/\\\\]+#', $dirSep, $path);
        } else {
            $path = preg_replace('#[/\\\\]+#', $dirSep, $path);
        }

        return $path;
    }

    /**
     * Sets access and modification time of file.
     *
     * A filename, an array of files, or a \Traversable instance to create
     *
     * @param int|null $time  The touch time as a Unix timestamp, if not supplied the current system time is used
     * @param int|null $atime The access time as a Unix timestamp, if not supplied the current system time is used
     *
     * @throws FileExcepton When touch fails
     */
    public function touch($time = null, $atime = null)
    {
        $results = [];

        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        foreach ($this->toIterable($this->path) as $file) {

            $touch = $time ? $this->hide->call('touch', $file, $time, $atime) : $this->hide->call('touch', $file);

            if (true !== $touch) {
                throw new FileException(sprintf('Failed to touch "%s".', $file));
            }
        }

        return $results;
    }

    /**
     * Create a hard link to the target file or directory.
     *
     * @param string $target
     * @param string $link
     */
    public function link($target = null, $link)
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        if ($target !== null) {
            $this->path = $target;
        }

        if (!strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            return $this->hide->call('symlink', $this->path, $link);
        }

        $mode = $this->hide->call('is_dir') ? 'J' : 'H';

        $this->hide->call('exec', sprintf("mklink /{$mode} \"{$link}\" \"{$this->path}\""));
    }

    /**
     * Returns shorten name of the given file.
     *
     * @param string $file
     * @param int    $lengthFirst
     * @param int    $lengthLast
     *
     * @return string
     */
    public function shortName($lengthFirst = 50, $lengthLast = 20)
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        return preg_replace("/(?<=.{{$lengthFirst}})(.+)(?=.{{$lengthLast}})/", '...', $this->path);
    }

    /**
     * Writes a random string name to a file.
     *
     * @param int    $length
     *
     * @return string
     */
    public function tempName($length = 5)
    {
        do {
            $test = $this->path.substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
        } while ($this->hide->call('file_exists', $test));

        return $test;
    }

    /**
     * Match path against an extended wildcard pattern.
     *
     * @param string $pattern
     *
     * @return bool
     */
    public function matchExtended(string $pattern): bool
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        $quoted = preg_quote($pattern, '~');

        $step1 = strtr($quoted, [
            '\?' => '[^/]', '\*' => '[^/]*', '/\*\*' => '(?:/.*)?', '#' => '\d+', '\[' => '[',
            '\]' => ']', '\-' => '-', '\{' => '{', '\}' => '}',
        ]);

        $step2 = preg_replace_callback('~{[^}]+}~', function ($part) {
            return '(?:'.substr(strtr($part[0], ',', '|'), 1, -1).')';
        }, $step1);

        $regex = $this->hide->call('rawurldecode', $step2);

        return (bool) preg_match("~^{$regex}$~", $this->path);
    }

    /**
     * Lock file for writing. You need to manually unlock().
     *
     * @param bool $block for non-blocking lock, set the parameter to false
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function lock($block = true): bool
    {
        if (!$this->handle) {
            $this->handle = @$this->hide->call('fopen', $this->path, 'cb+');

            if (!$this->handle) {
                $error = error_get_last();

                throw new \RuntimeException(sprintf('Opening file for writing failed on error %s', $error['message']));
            }
        }
        $lock = $block ? LOCK_EX : LOCK_EX | LOCK_NB;

        // Some filesystems do not support file locks, only fail if another process holds the lock.
        $this->locked = flock($this->handle, $lock, $wouldblock) || !$wouldblock;

        return $this->locked;
    }

    /**
     * Returns true if file has been locked for writing.
     *
     * @return bool|null true = locked, false = failed, null = not locked
     */
    public function locked()
    {
        return $this->locked;
    }

    /**
     * Unlock file.
     *
     * @return bool
     */
    public function unlock()
    {
        if (!$this->handle) {
            return false;
        }

        if ($this->locked) {
            $this->hide->call('flock', $this->handle, LOCK_UN);

            $this->locked = false;
        }
        $this->hide->call('fclose', $this->handle);
        $this->handle = false;

        return true;
    }

    /**
     * Free the file instance.
     */
    public function free()
    {
        if ($this->locked) {
            $this->unlock();
        }

        unset($this->path);
    }

    /**
     * List files and directories inside the specified path.
     *
     * @param int $sorting
     *
     * @return array
     */
    public function scan(int $sorting = SCANDIR_SORT_NONE): array
    {
        if (isset($this->path)) {
            try {
                $contents = [];

                $flags = \FilesystemIterator::KEY_AS_PATHNAME
                    | \FilesystemIterator::CURRENT_AS_FILEINFO
                    | \FilesystemIterator::SKIP_DOTS;

                $dirIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->path, $flags));

                foreach ($dirIterator as $path) {
                    $contents[] = $path;
                }

                natsort($contents);

                return $contents;
            } catch (FileException $e) {
                return $this->hide->call('scandir', $this->path, $sorting);
            }
        }
    }

    /**
     * Retrieves the path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

     /**
     * Returns available space on filesystem or disk partition.
     *
     * @return string
     */
    public function freeSpace(): string
    {
        if (isset($this->path) && $this->hide->call('is_dir', $this->path)) {
            $results = $this->exists()
                ? $this->hide->call('disk_free_space', $this->path) : '';
        }

        return (string) $results;
    }

    /**
     * Converts to path.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getPath();
    }
}
