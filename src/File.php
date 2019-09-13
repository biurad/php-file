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

class File extends Handler
{
    /**
     * Returns the files contents.
     *
     * @param bool $lock
     *
     * @throws FileException if could not get contents
     *
     * @return string
     */
    public function getContents(bool $lock = false)
    {
        if (true === $lock) {
            return $this->sharedGet($this->path);
        }

        return $this->hide->call('file_get_contents', $this->path);

        throw new FileException(sprintf('Reading contents from %s failed', $this->path));
    }

    /**
     * Get contents of a file with shared access.
     *
     * @param string $path
     *
     * @return string
     */
    protected function sharedGet($path)
    {
        $contents = '';

        $handle = $this->hide->call('fopen', $path, 'rb');

        if ($handle) {
            try {
                if ($this->hide->call('flock', $handle, LOCK_SH)) {
                    $this->hide->call('clearstatcache', true, $path);

                    $contents = $this->hide->call('fread', $handle, $this->hide->call('filesize', $path) ?: 1);

                    $this->hide->call('flock', $handle, LOCK_UN);
                }
            } finally {
                $this->hide->call('fclose', $handle);
            }
        }

        return $contents;
    }

    /**
     * Appends data to a file.
     *
     * @param string $data
     *
     * @return boolean
     */
    public function append($data)
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        $bites = $this->hide->call('file_put_contents', $this->path, $data, FILE_APPEND | LOCK_EX);

        return $bites !== false;
    }

    /**
     * Updates a file.
     *
     * @param string $data
     *
     * @return boolean
     */
    public function update($data)
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        $bites = $this->hide->call('file_put_contents', $this->path, $data, LOCK_EX);

        return $bites !== false;
    }

    /**
     * Copies a file.
     *
     * If the target file is older than the origin file, it's always overwritten.
     *
     * @param string $destination
     *
     * @return boolean
     */
    public function copyTo($destination)
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        // https://bugs.php.net/bug.php?id=64634
        if (!$this->isReadable()) {
            throw new FileException(sprintf('Failed to copy "%s" to "%s" because source file could not be opened for reading.', $this->path, $destination));
        }

        return $this->hide->call('copy', $this->path, $destination);
    }

    /**
     * Returns the file size.
     *
     * @param string $destination
     *
     * @return boolean
     */
    public function getSize()
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        return $this->hide->call('filesize', $this->path);
    }

    /**
     * Returns the mime-type.
     *
     * @return string
     */
    public function getMimeType()
    {
        if (!isset($this->path)) {
            throw new FileException('The required path is not in session');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $this->path);
        finfo_close($finfo);

        return $mime;
    }
}
