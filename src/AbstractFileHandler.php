<?php

/*
 * The Biurad Toolbox FileHandler.
 *
 * This file is used to get rid of some discouraged
 * funtion usuage when it comes to php filesystem.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 */
declare(strict_types=1);

namespace BiuradPHP\Toolbox\FilePHP;

/**
 * Implements Universal File Reader.
 *
 * This file is used to get rid of some discouraged
 * funtion usuage when it comes to php filesystem.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 * @license MIT
 */
class AbstractFileHandler
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var resource
     */
    protected $handle;

    /**
     * @var bool|null
     */
    protected $locked;

    /**
     * @var string raw file contents
     */
    protected $raw;

    /**
     * @var string parsed file contents
     */
    protected $content;

    /**
     * @var string parsed file contents and get all
     */
    protected $contents;

    /**
     * @var Silencer parsed file contents and get all
     */
    protected $silencer;

    /**
     * @var mixed For user usuage
     */
    const FILE = false;

    /**
     * @var array|FileHandler[]
     */
    protected static $instances = [];

    /**
     * Get file instance.
     *
     * @param string $filename
     *
     * @return static
     */
    public static function getInstance($filename = null)
    {
        if (!\is_string($filename) && $filename) {
            throw new \InvalidArgumentException('Filename should be non-empty string');
        }
        if (!isset(static::$instances[$filename])) {
            static::$instances[$filename] = new static();
            static::$instances[$filename]->_init($filename);
            $filename == null ?: static::$instances[$filename]->load();
        }

        return static::$instances[$filename];
    }

    /**
     * Allow construct being used.
     */
    public function __construct($path = null)
    {
        if ($path !== null) {
            $this->path = $this->toIterable($path);
        }
        $this->silencer = new Silencer();

        return (string) isset($this->path);
    }

    /**
     * Unlock file when the object gets destroyed.
     */
    public function __destruct()
    {
        if ($this->locked()) {
            $this->unlock();
        }
    }

    protected function __clone()
    {
        $this->handle = null;
        $this->locked = false;
    }

    /**
     * Set filename.
     *
     * @param $filename
     */
    protected function _init($filename): iterable
    {
        $this->path = $filename;

        return \is_array($this->path) || $this->path instanceof \Traversable ? $this->path : [$this->path];
    }

    /**
     * Get/Set the file location.
     *
     * @param string $var
     * @param bool   $get
     *
     * @return string
     */
    public function load($var = null): string
    {
        if ($var !== null) {
            $this->path = $this->toIterable($var);
        }

        return (string) $this->path;
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
     * Extract the file name from a file path.
     *
     * @return string
     */
    public function name()
    {
        return $this->silencer->call('pathinfo', $this->path, PATHINFO_FILENAME);
    }

    /**
     * Get the contents of a file.
     *
     * @param bool $lock
     *
     * @throws FileException if could not get contents
     *
     * @return string
     */
    public function get(bool $lock = false)
    {
        if ($this->is_file()) {
            return $lock ? $this->sharedGet($this->path) : $this->silencer->call('file_get_contents', $this->path);
        }

        throw new FileException(sprintf('File does not exist at path %s', $this->path));
    }

    /**
     * Get contents of a file with shared access.
     *
     * @param string $path
     *
     * @return string
     */
    public function sharedGet($path)
    {
        $contents = '';

        $handle = $this->silencer->call('fopen', $path, 'rb');

        if ($handle) {
            try {
                if ($this->silencer->call('flock', $handle, LOCK_SH)) {
                    $this->silencer->call('clearstatcache', true, $path);

                    $contents = $this->silencer->call('fread', $handle, $this->silencer->call('filesize', $path) ?: 1);

                    $this->silencer->call('flock', $handle, LOCK_UN);
                }
            } finally {
                $this->silencer->call('fclose', $handle);
            }
        }

        return $contents;
    }

    /**
     * Binary safe to open file.
     *
     * @param string $mode
     *
     * @return string|null
     */
    public function open($mode = 'rb')
    {
        $contents = null;

        if ($realPath = $this->path) {
            $handle = $this->silencer->call('fopen', $realPath, $mode);
            $contents = $this->silencer->call('fread', $handle, $this->silencer->call('filesize', $realPath) ?: 4096);
            $this->silencer->call('fclose', $handle);
        }

        return $contents;
    }

    /**
     * Quickest way for getting first file line.
     *
     *
     * @return string|null
     */
    public function first_line()
    {
        if ($this->exists()) {
            $cacheRes = $this->silencer->call('fopen', $this->path, 'rb');
            $firstLine = $this->silencer->call('fgets', $cacheRes);
            $this->silencer->call('fclose', $cacheRes);

            return $firstLine;
        }

        return null;
    }

    /**
     * Check if the file contains the specified string.
     *
     * @param string $str
     *
     * @return bool
     */
    public function has_string(string $str): bool
    {
        if (isset($this->path)) {
            $handle = $this->silencer->call('fopen', $this->path, 'r');

            if ($handle === false) {
                return false;
            }

            $valid = false;

            $len = max(2 * strlen($str), 256);
            $prev = '';

            while (!$this->silencer->call('feof', $handle)) {
                $cur = $this->silencer->call('fread', $handle, $len);

                if (strpos($prev.$cur, $str) !== false) {
                    $valid = true;
                    break;
                }
                $prev = $cur;
            }

            $this->silencer->call('fclose', $handle);

            return $valid;
        }
    }

    /**
     * Match path against an extended wildcard pattern.
     *
     * @param string $pattern
     *
     * @return bool
     */
    public function match_extended(string $pattern): bool
    {
        if (isset($this->path)) {
            $quoted = preg_quote($pattern, '~');

            $step1 = strtr($quoted, [
                '\?' => '[^/]', '\*' => '[^/]*', '/\*\*' => '(?:/.*)?', '#' => '\d+', '\[' => '[',
                '\]' => ']', '\-' => '-', '\{' => '{', '\}' => '}',
            ]);

            $step2 = preg_replace_callback('~{[^}]+}~', function ($part) {
                return '(?:'.substr(strtr($part[0], ',', '|'), 1, -1).')';
            }, $step1);

            $regex = $this->silencer->call('rawurldecode', $step2);

            return (bool) preg_match("~^{$regex}$~", $this->path);
        }
    }

    /**
     * Get/set raw file contents.
     *
     * @param string $var
     *
     * @return string
     */
    protected function _raw($var = null)
    {
        if ($var !== null) {
            $this->raw = (string) $var;
            $this->content = null;
        }

        if (!\is_string($this->raw)) {
            $this->raw = $this->load();
        }

        return $this->raw;
    }

    /**
     * Get/set parsed file contents.
     *
     * @param mixed $var
     *
     * @return string|array
     *
     * @throws \RuntimeException
     */
    protected function _content($var = null)
    {
        if ($var !== null) {
            $this->content = $this->check($var);

            // Update RAW, too.
            $this->raw = $this->_encode($this->content);
        } elseif ($this->content === null) {
            // Decode RAW file.
            try {
                $this->content = $this->_decode($this->_raw());
            } catch (\Exception $e) {
                throw new \RuntimeException(sprintf('Failed to read %s: %s', $this->path, $e->getMessage()), 500, $e);
            }
        }

        return $this->content;
    }

    /**
     * Write the contents of a file.
     *
     * @param string $contents
     * @param bool   $lock
     *
     * @return int|bool
     */
    public function put($contents, $lock = false)
    {
        if (isset($this->path)) {
            return $this->silencer->call('file_put_contents', $this->path, $contents, $lock ? LOCK_EX : 0);
        }
    }

    /**
     * Write the contents of a file, replacing it atomically if it already exists.
     *
     * @param string $path
     * @param string $content
     *
     * @return void
     */
    public function replace($content = null)
    {
        if ($content !== null) {
            $content = $this->_content($content);
        }

        // If the path already exists and is a symlink, get the real path...
        $this->silencer->call('clearstatcache', true, $this->path);

        $path = $this->silencer->call('realpath', $this->path) ?: $this->path;

        $tempPath = $this->silencer->call('tempnam', $this->dirname(), $this->silencer->call('basename', $path));

        // Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
        $this->silencer->call('chmod', $tempPath, 0777 - umask());

        $this->silencer->call('file_put_contents', $tempPath, $content);

        $this->silencer->call('rename', $tempPath, $path);
    }

    /**
     * Save file.
     *
     * @param mixed $data optional data to be saved, usually array
     *
     * @throws \RuntimeException
     */
    public function save($data = null)
    {
        if ($data !== null) {
            $data = $this->_content($data);
        }

        $filename = $this->path;
        $dir = $this->silencer->call('dirname', $filename);

        if (!$this->mkdir($dir)) {
            throw new \RuntimeException('Creating directory failed for '.$filename);
        }

        try {
            if ($this->handle) {
                $tmp = true;
                // As we are using non-truncating locking, make sure that the file is empty before writing.
                if ($this->silencer->call('ftruncate', $this->handle, 0) === false || @$this->silencer->call('fwrite', $this->handle, $data) === false) {
                    // Writing file failed, throw an error.
                    $tmp = false;
                }
            } else {
                // Create file with a temporary name and rename it to make the save action atomic.
                $tmp = $this->tempname($filename);
                if ($this->silencer->call('file_put_contents', $tmp, $data) === false) {
                    $tmp = false;
                } elseif ($this->silencer->call('rename', $tmp, $filename) === false) {
                    $this->silencer->call('unlink', $tmp);
                    $tmp = false;
                }
            }
        } catch (\Exception $e) {
            $tmp = false;
        }

        if ($tmp === false) {
            throw new \RuntimeException('Failed to save file '.$filename);
        }

        // Touch the directory as well, thus marking it modified.
        $this->silencer->call('touch', $dir);
    }

    /**
     * Writes a random string name to a file.
     *
     * @param string $filename
     * @param int    $length
     *
     * @return string
     */
    public function tempname($filename, $length = 5)
    {
        do {
            $test = $filename.substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
        } while ($this->silencer->call('file_exists', $test));

        return $test;
    }

    /**
     * Check contents and make sure it is in correct format.
     *
     * Override in derived class.
     *
     * @param string|array $value
     *
     * @return string
     */
    public function check($value)
    {
        if (is_array($value)) {
            $this->contents = implode('|', array_map([$this, 'check'], $value));
        }
        if (is_bool($value)) {
            $this->contents = $value ? 'true' : 'false';
        }
        if (is_string($value)) {
            $this->contents = $this->silencer->call('addslashes', $value);
        }
        if (null === $value) {
            $this->contents = 'null';
        }

        return (string) $this->contents;
    }

    /**
     * Encode contents into RAW string.
     *
     * Override in derived class.
     *
     * @param string $var
     *
     * @return string
     */
    protected function _encode($var): string
    {
        return (string) $var;
    }

    /**
     * Decode RAW string into contents.
     *
     * Override in derived class.

     *
     * @param string $var
     *
     * @return string
     */
    protected function _decode($var, $options = null): string
    {
        return (string) $this->silencer->call('unserialize', $var, $options);
    }

    /**
     * Attempts to create the directory specified by pathname.
     *
     * @param string $dir
     *
     * @return bool
     */
    public function mkdir($dir = null)
    {
        if ($dir !== null) {
            $this->path = $dir;
        }

        // Silence error for open_basedir; should fail in mkdir instead.
        if (@$this->is_dir()) {
            return true;
        }

        $success = $this->silencer->call('mkdir', $this->path, 0777, true);

        if (!$success) {
            // Take yet another look, make sure that the folder doesn't exist.
            $this->clear_cache();
            if (!@$this->is_dir()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Removes a directory (and its contents) recursively.
     *
     * @param string $dir              The directory to be deleted recursively
     * @param bool   $traverseSymlinks Delete contents of symlinks recursively
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function remove_dir($dir = null, $traverseSymlinks = false): bool
    {
        if ($dir !== null) {
            $this->path = $dir;
        }

        if (!$this->exists()) {
            return true;
        }

        if (!$this->is_dir()) {
            throw new \RuntimeException('Given path is not a directory');
        }

        if ($traverseSymlinks || !$this->silencer->call('is_link', $this->path)) {
            foreach ($this->scan_dir() as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $currentPath = $this->path.'/'.$file;

                if ($this->silencer->call('is_dir', $currentPath)) {
                    $this->remove_dir($currentPath, $traverseSymlinks);
                } elseif (!$this->silencer->call('unlink', $currentPath)) {
                    // @codeCoverageIgnoreStart
                    throw new \RuntimeException(sprintf('Unable to delete %s', $currentPath));
                    // @codeCoverageIgnoreEnd
                }
            }
        }

        // @codeCoverageIgnoreStart
        // Windows treats removing directory symlinks identically to removing directories.
        if (!defined('PHP_WINDOWS_VERSION_MAJOR') && $this->silencer->call('is_link', $this->path)) {
            if (!$this->delete()) {
                throw new \RuntimeException(sprintf('Unable to delete %s', $dir));
            }
        } else {
            if (!$this->silencer->call('rmdir', $this->path)) {
                throw new \RuntimeException(sprintf('Unable to delete %s', $dir));
            }
        }

        return true;
        // @codeCoverageIgnoreEnd
    }

    /**
     * See unlink() or unset().
     *
     * @return boolean
     */
    public function delete(): bool
    {
        if (isset($this->path)) {
            $path = $this->path;

            $success = true;
            try {
                $this->silencer->call('unlink', $path);
                $success = true;
            } catch (ErrorException $e) {
                $success = false;
            }

            return $success;
        }
    }

    /**
     * Checks whether the file is a directory.
     *
     * @return boolean
     */
    public function is_dir(): bool
    {
        if (isset($this->path)) {
            $this->contents = $this->silencer->call('is_dir', $this->path) ?? $this->path;
        }

        return (bool) $this->contents;
    }

    /**
     * Returns the result of check if given directory is empty.
     *
     * @param array $exclude
     *
     * @return bool
     */
    public function is_empty($exclude = []): bool
    {
        if (!$this->readable()) {
            return false;
        }

        $hd = $this->silencer->call('opendir', $this->path);
        if (!$hd) {
            return false;
        }
        while (false !== ($entry = $this->silencer->call('readdir', $hd))) {
            if (($entry !== '.' && $entry !== '..')) {
                if (!in_array($entry, $exclude)) {
                    return false;
                }
            }
        }
        $this->silencer->call('closedir', $hd);

        return true;
    }

    /**
     * Checks whether the file is a regular file.
     *
     * @return boolean
     */
    public function is_file(): bool
    {
        if (isset($this->path)) {
            $this->contents = $this->silencer->call('is_file', $this->path) ?? $this->path;
        }

        return (bool) $this->contents;
    }

    /**
     * Returns the path of the parent directory.
     *
     * @param int $levels
     *
     * @return string
     */
    public function dirname(): string
    {
        if (isset($this->path)) {
            $this->contents = $this->silencer->call('pathinfo', $this->path, PATHINFO_DIRNAME);
        }

        return (string) $this->contents;
    }

    /**
     * Check if file exits.
     *
     * @return bool
     */
    public function exists(): bool
    {
        if (isset($this->path)) {
            $this->contents = $this->silencer->call('file_exists', $this->path) ?? $this->path;
        }

        return (bool) $this->contents;
    }

    /**
     * List files and directories inside the specified path.
     *
     * @param int $sorting
     *
     * @return array
     */
    public function scan_dir(int $sorting = SCANDIR_SORT_NONE): array
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
                return $this->silencer->call('scandir', $this->path, $sorting);
            }
        }
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
            if (!$this->mkdir($this->dirname())) {
                throw new \RuntimeException(sprintf('Creating directory failed for %s', $this->path));
            }
            $this->handle = @$this->silencer->call('fopen', $this->path, 'cb+');
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
            $this->silencer->call('flock', $this->handle, LOCK_UN);
            $this->locked = null;
        }
        $this->silencer->call('fclose', $this->handle);
        $this->handle = null;

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
        $this->content = null;
        $this->raw = null;
        $this->contents = null;

        unset(static::$instances[$this->path]);
    }

    /**
     * Set the writable bit on a file to the minimum value that allows the user running PHP to write to it.
     *
     * @param string $filename The filename to set the writable bit on
     * @param bool   $writable Whether to make the file writable or not
     *
     * @return boolean
     */
    public function writable(bool $writable = true): bool
    {
        if (isset($this->path) && !$this->exists($this->path)) {
            return $this->set_permission($this->path, $writable, 2);
        }

        return $this->load() && $this->is_dir() && $this->silencer->call('is_writable', $this->path);
    }

    /**
     * Set the readable bit on a file to the minimum value that allows the user running PHP to read to it.
     *
     * @param string $filename The filename to set the readable bit on
     * @param bool   $readable Whether to make the file readable or not
     *
     * @return boolean
     */
    public function readable(bool $readable = true): bool
    {
        if (isset($this->path) && !$this->exists($this->path)) {
            return $this->set_permission($this->path, $readable, 4);
        }

        return $this->load() && $this->is_dir() && $this->silencer->call('is_readable', $this->path);
    }

    /**
     * Set the executable bit on a file to the minimum value that allows the user running PHP to read to it.
     *
     * @param bool $executable Whether to make the file executable or not
     *
     * @return boolean
     */
    public function executable($executable = true): bool
    {
        return $this->set_permission($this->path, $executable, 1);
    }
}
