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
 */
class FileHandler extends AbstractFileHandler
{
    /**
     * Get relative path between target and base path. If path isn't relative, return full path.
     *
     * @param string $path
     * @param string $base
     *
     * @return string
     */
    public function getRelativePathDotDot($path, $base): string
    {
        // Normalize paths.
        $base = preg_replace('![\\\/]+!', '/', $base);
        $path = preg_replace('![\\\/]+!', '/', $path);

        if ($path === $base) {
            return '';
        }

        $baseParts = explode('/', ltrim($base, '/'));
        $pathParts = explode('/', ltrim($path, '/'));

        array_pop($baseParts);
        $lastPart = array_pop($pathParts);
        foreach ($baseParts as $i => $directory) {
            if (isset($pathParts[$i]) && $pathParts[$i] === $directory) {
                unset($baseParts[$i], $pathParts[$i]);
            } else {
                break;
            }
        }
        $pathParts[] = $lastPart;
        $path = str_repeat('../', count($baseParts)).implode('/', $pathParts);

        return '' === $path
            || strpos($path, '/') === 0
            || false !== ($colonPos = strpos($path, ':')) && ($colonPos < ($slashPos = strpos($path, '/')) || false === $slashPos)
            ? "./$path" : $path;
    }

    /**
     * Get relative path between target and base path. If path isn't relative, return full path.
     *
     * @param string       $path
     * @param mixed|string $base
     *
     * @return string
     */
    public function getRelativePath($path, $base = null): string
    {
        if (null !== $base) {
            $base = preg_replace('![\\\/]+!', '/', $base);
            $path = preg_replace('![\\\/]+!', '/', $path);
            if (strpos($path, $base) === 0) {
                $path = ltrim(substr($path, strlen($base)), '/');
            }
        }

        return (string) $path;
    }

    /**
     * Format Memory.
     *
     * @param mixed $memory
     *
     * @return string
     */
    private function format_memory($memory): string
    {
        if ($memory >= 1024 * 1024 * 1024 * 1024) {
            return sprintf('%.1f TB', $memory / 1024 / 1024 / 1024 / 1024);
        }

        if ($memory >= 1024 * 1024 * 1024) {
            return sprintf('%.1f GiB', $memory / 1024 / 1024 / 1024);
        }

        if ($memory >= 1024 * 1024) {
            return sprintf('%.1f MB', $memory / 1024 / 1024);
        }

        if ($memory >= 1024) {
            return sprintf('%d KiB', $memory / 1024);
        }

        return (string) sprintf('%d Bytes', $memory);
    }

    /**
     * Format Path.
     *
     * @param string $baseDir
     *
     * @return string
     */
    public function format_path(string $baseDir): string
    {
        return preg_replace('~^'.preg_quote($baseDir, '~').'~', '.', $this->path);
    }

    /**
     * Returns the filename component of a path.
     *
     * @return string
     */
    public function basename(): string
    {
        if (isset($this->path)) {
            $this->contents = $this->exists() ? $this->silencer->call('pathinfo', $this->path, PATHINFO_BASENAME) : '';
        }

        return (string) $this->contents;
    }

    /**
     * Extract the file extension from a file path.
     *
     * @return string
     */
    public function extension(): string
    {
        if (strpos($this->path, '?') !== false) {
            $path = preg_replace('#\?(.*)#', '', $this->path);
        }

        $ext = $this->silencer->call('pathinfo', $path, PATHINFO_EXTENSION);
        $ext = strtolower($ext);

        return $ext;
    }

    /**
     * @return string
     */
    public function real(): string
    {
        if (isset($this->path)) {
            return realpath($this->path);
        }
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
     * Strip off the extension if it exists.
     *
     * @return string
     */
    public function strip_extention(): string
    {
        $reg = '/\.'.preg_quote($this->extension(), null).'$/';
        $path = preg_replace($reg, '', $this->path);

        return $path;
    }

    /**
     * Get the file type of a given file.
     *
     * @return string
     */
    public function type()
    {
        if (isset($this->path)) {
            return filetype($this->path);
        }
    }

    /**
     * Get the mime-type of a given file.
     *
     * @return string|false
     */
    public function mime_type()
    {
        if (isset($this->path)) {
            return $this->silencer->call('finfo_file', $this->silencer->call('finfo_open', FILEINFO_MIME_TYPE), $this->path);
        }
    }

    /**
     * Changes the file group.
     *
     * @param mixed $group
     *
     * @return bool
     */
    public function change_group(mixed $group): bool
    {
        if (isset($this->path)) {
            return Silencer::call('chgrp', $this->path, $group);
        }
    }

    /**
     * Changes the file owner.
     *
     * @param mixed $user
     *
     * @return bool
     */
    public function change_owner(mixed $user): bool
    {
        if (isset($this->path)) {
            return Silencer::call('chown', $this->path, $user);
        }
    }

    /**
     * Clears the file status cache.
     *
     * @param bool $clear_realpath_cache
     *
     * @return string
     */
    public function clear_cache(bool $clear_realpath_cache = true): string
    {
        if (isset($this->path)) {
            $this->contents = $this->exists()
                ? Silencer::call('clearstatcache', $clear_realpath_cache, $this->path) : '';
        }

        return (string) $this->contents;
    }

    /**
     * Copies a file.
     *
     * If the target file is older than the origin file, it's always overwritten.
     *
     * @param string $targetFile The target filename
     *
     * @throws FileException When path doesn't exist
     */
    public function copy($targetFile)
    {
        // https://bugs.php.net/bug.php?id=64634
        if (!Silencer::call('is_readable', $this->path)) {
            throw new FileException(sprintf('Failed to copy "%s" to "%s" because source file could not be opened for reading.', $this->path, $targetFile));
        }

        return Silencer::call('copy', $this->path, $targetFile);

        if (!Silencer::call('is_file', $targetFile)) {
            throw new FileException(sprintf('Failed to copy "%s" to "%s".', $this->path, $targetFile));
        }
    }

    /**
     * Changes file mode.
     *
     * @param string $filename
     * @param int    $perm
     * @param int    $add
     *
     * @return bool
     */
    public function chmod($perm, $add): bool
    {
        if (isset($this->path)) {
            return Silencer::call('chmod', $this->path, (fileperms($this->path) | $this->silencer->call('intval', '0'.$perm.$perm.$perm, 8)) ^ $add);
        }
    }

    /**
     * Returns available space on filesystem or disk partition.
     *
     * @return string
     */
    public function free_space(): string
    {
        if (isset($this->path) && Silencer::call('is_dir', $this->path)) {
            $this->contents = $this->exists()
                ? $this->format_memory(Silencer::call('disk_free_space', $this->path)) : '';
        }

        return (string) $this->contents;
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
        foreach ($this->toIterable($this->path) as $file) {
            $touch = $time ? Silencer::call('touch', $file, $time, $atime) : Silencer::call('touch', $file);
            if (true !== $touch) {
                throw new FileException(sprintf('Failed to touch "%s".', $file));
            }
        }
    }

    /**
     * Create a hard link to the target file or directory.
     *
     * @param string $target
     * @param string $link
     */
    public function link($target = null, $link)
    {
        if ($target !== null) {
            $this->path = $target;
        }

        if (!strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            return $this->silencer->call('symlink', $this->path, $link);
        }

        $mode = $this->is_dir() ? 'J' : 'H';

        Silencer::call('exec', sprintf("mklink /{$mode} \"{$link}\" \"{$this->path}\""));
    }

    /**
     * Get file or directory size.
     *
     * @param string $path
     *
     * @return string
     */
    public function size(): string
    {
        if (Silencer::call('is_file', $this->path)) {
            $size = Silencer::call('filesize', $this->path) ?: 0;
        } else {
            $size = 0;
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->path, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS)) as $file) {
                $size += $file->getSize();
            }
        }

        return (string) $this->format_memory($size);
    }

    /**
     * Returns the total size of a filesystem or disk partition.
     *
     * @return string
     */
    public function total_space(): string
    {
        if (isset($this->path)) {
            $this->contents = $this->exists()
                ? $this->format_memory(Silencer::call('disk_total_space', $this->path)) : '';
        }

        return (string) $this->contents;
    }

    /**
     * Reads entire file into an array.
     *
     * @return bool|array
     */
    public function file(): array
    {
        if (isset($this->path)) {
            $this->contents = Silencer::call('file', $this->path);
        }

        return (array) $this->contents;
    }

    /**
     * Returns information about a file or symbolic link.
     *
     * @return array
     */
    public function info(): array
    {
        if (isset($this->path)) {
            return Silencer::call('lstat', $this->path);
        }
    }

    /**
     * Rename file in the filesystem if it exists.
     *
     * @param $filename
     *
     * @return bool
     */
    public function rename($filename)
    {
        if (isset($this->path)) {
            if ($this->exists() && !Silencer::call('rename', $this->path, $filename)) {
                return false;
            }

            unset(static::$instances[$this->path]);
            static::$instances[$filename] = $this;

            $this->path = $filename;

            return true;
        }
    }

    /**
     * Returns Path info of the file.
     *
     * @param string $path
     * @param int    $options
     */
    public function pathinfo(int $options = null)
    {
        if (isset($this->path)) {
            $this->contents = $this->exists() ? $this->silencer->call('pathinfo', $this->path, $options) : '';
        }

        return $this->contents;
    }

    /**
     *  Reads a file and writes it to the output buffer.
     *
     * @return bool
     */
    public function readfile(): bool
    {
        if (isset($this->path)) {
            $this->contents = $this->exists() ?? Silencer::call('readfile', $this->path);
        }

        return (bool) $this->contents;
    }

    /**
     * Return file modification time.
     *
     * @return int|bool timestamp or false if file doesn't exist
     */
    public function modified(): bool
    {
        if (isset($this->path)) {
            return $this->is_file() ? Silencer::call('filemtime', $this->path) : false;
        }
    }

    /**
     * Return file creation time.
     *
     * @return int|bool
     */
    public function creation(): bool
    {
        if (isset($this->path)) {
            return $this->is_file() ? Silencer::call('filectime', $this->this->filename) : time();
        }
    }

    /**
     * Excute a command or app passed to shell.
     *
     * @param bool $shell
     *
     * @return string
     */
    public function exec(bool $shell = true): string
    {
        if (isset($this->path)) {
            $this->contents = $shell ? Silencer::call('shell_exec', $this->path) : Silencer::call('exec', $this->path);
        }

        return (string) $this->contents;
    }

    /**
     * Excute a command or file to both shell or web.
     *
     * @param bool $file
     *
     * @return bool|int|void
     */
    public function passthru(bool $file = true)
    {
        if (isset($this->path)) {
            $handle = $this->silencer->call('fopen', $this->path, 'r');
            $this->contents = $file ? Silencer::call('fpassthru', $handle) : Silencer::call('passthru', $this->path);
            $this->silencer->call('fclose', $handle);
        }

        return $this->contents;
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
    public function short_name($lengthFirst = 10, $lengthLast = 10)
    {
        return preg_replace("/(?<=.{{$lengthFirst}})(.+)(?=.{{$lengthLast}})/", '...', $this->path);
    }

    /**
     * Returns the number of files in a given directory.
     *
     * @param array  $exclude
     * @param string $fileExtension
     *
     * @return int
     */
    public function count_dir($fileExtension = '')
    {
        $search = !empty($fileExtension) ? '*'.$fileExtension : '*';

        return count(Silencer::call('glob', $this->path.$search));
    }

    /**
     * @param string   $filename
     * @param bool     $isFlag
     * @param int|null $perm
     *
     * @return bool
     */
    protected function set_permission($filename = null, $isFlag, $perm)
    {
        $stat = @$this->silencer->call('stat', $this->path);

        if (null !== $filename) {
            $this->path = $filename;
        }

        if ($stat === false) {
            return false;
        }

        // We're on Windows
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            //@codeCoverageIgnoreStart
            return true;
            //@codeCoverageIgnoreEnd
        }

        list($myuid, $mygid) = [Silencer::call('posix_geteuid'), Silencer::call('posix_getgid')];

        $isMyUid = $stat['uid'] === $myuid;
        $isMyGid = $stat['gid'] === $mygid;

        //@codeCoverageIgnoreStart
        if ($isFlag) {
            // Set only the user writable bit (file is owned by us)
            if ($isMyUid) {
                return Silencer::call('chmod', $this->path, Silencer::call('fileperms', $this->path) | $this->silencer->call('intval', '0'.$perm.'00', 8));
            }

            // Set only the group writable bit (file group is the same as us)
            if ($isMyGid) {
                return Silencer::call('chmod', $this->path, Silencer::call('fileperms', $this->path) | $this->silencer->call('intval', '0'.$perm.$perm.'0', 8));
            }

            // Set the world writable bit (file isn't owned or grouped by us)
            return Silencer::call('chmod', $this->path, Silencer::call('fileperms', $this->path) | $this->silencer->call('intval', '0'.$perm.$perm.$perm, 8));
        }

        // Set only the user writable bit (file is owned by us)
        if ($isMyUid) {
            $add = $this->silencer->call('intval', '0'.$perm.$perm.$perm, 8);

            return $this->chmod($perm, $add);
        }

        // Set only the group writable bit (file group is the same as us)
        if ($isMyGid) {
            $add = $this->silencer->call('intval', '00'.$perm.$perm, 8);

            return $this->chmod($perm, $add);
        }

        // Set the world writable bit (file isn't owned or grouped by us)
        $add = $this->silencer->call('intval', '000'.$perm, 8);

        return $this->chmod($perm, $add);
        //@codeCoverageIgnoreEnd
    }

    /**
     * Returns the file permissions as a nice string, like -rw-r--r-- or false if the file is not found.
     *
     * @param string $file  The name of the file to get permissions form
     * @param int    $perms numerical value of permissions to display as text
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function permission(string $file = null, int $perms = null): string
    {
        if (null !== $file) {
            $this->path = $file;
        }

        if (null === $perms) {
            if (!$this->exists()) {
                return false;
            }

            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $perms = Silencer::call('fileperms', $this->path);
        }

        //@codeCoverageIgnoreStart
        $info = 'u'; // undefined
        if (($perms & 0xC000) === 0xC000) { // Socket
            $info = 's';
        } elseif (($perms & 0xA000) === 0xA000) { // Symbolic Link
            $info = 'l';
        } elseif (($perms & 0x8000) === 0x8000) { // Regular
            $info = '-';
        } elseif (($perms & 0x6000) === 0x6000) { // Block special
            $info = 'b';
        } elseif (($perms & 0x4000) === 0x4000) { // Directory
            $info = 'd';
        } elseif (($perms & 0x2000) === 0x2000) { // Character special
            $info = 'c';
        } elseif (($perms & 0x1000) === 0x1000) { // FIFO pipe
            $info = 'p';
        }
        //@codeCoverageIgnoreEnd

        // Owner
        $info .= (($perms & 0x0100) ? 'r' : '-');
        $info .= (($perms & 0x0080) ? 'w' : '-');
        /* @noinspection NestedTernaryOperatorInspection */
        $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));

        // Group
        $info .= (($perms & 0x0020) ? 'r' : '-');
        $info .= (($perms & 0x0010) ? 'w' : '-');
        /* @noinspection NestedTernaryOperatorInspection */
        $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));

        // All
        $info .= (($perms & 0x0004) ? 'r' : '-');
        $info .= (($perms & 0x0002) ? 'w' : '-');
        /* @noinspection NestedTernaryOperatorInspection */
        $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));

        return $info;
    }
}
