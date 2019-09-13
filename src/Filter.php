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

class Filter
{
    /**
     * @var array
     */
    protected $typeCache = [];

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * Checks wether an path is of the correct type, dir or file.
     *
     * @param string $type
     * @param string $path
     *
     * @return bool
     */
    public function isCorrectType($type, $path)
    {
        if (!$type) {
            return true;
        }

        if (!isset($this->typeCache[$path])) {
            $this->typeCache[$path] = is_file($path) ? 'file' : 'dir';
        }

        return $this->typeCache[$path] === $type;
    }

    /**
     * Filters a batch of filesystem entries.
     *
     * @param array $contents
     *
     * @return array
     */
    public function filter(array $contents)
    {
        $filtered = array();
        $this->typeCache = array();

        foreach ($contents as $item) {
            $passed = true;

            foreach ($this->filters as $filter) {
                $correctType = $this->isCorrectType($filter['type'], $item);

                if ($correctType and preg_match($filter['pattern'], $item) !== $expected) {
                    $passed = false;
                }
            }

            if ($passed) {
                $filtered[] = $item;
            }
        }

        return $contents;
    }

    /**
     * Adds a filter.
     *
     * @param string $filter
     * @param bool   $expected
     * @param string $type
     */
    public function addFilter($filter, $expected = true, $type = null)
    {
        $filter = '#'.$filter.'#';

        $this->filters[] = array(
            'pattern' => $filter,
            'expected' => $expected,
            'type' => $type,
        );
    }

    /**
     * Ensures an extension.
     *
     * @param string $extension
     */
    public function hasExtension($extension)
    {
        $filter = '\\.['.ltrim($extension, '.').']$';

        $this->addFilter($filter, true, 'file');
    }

    /**
     * Blocks by extension.
     *
     * @param string $extension
     */
    public function blockExtension($extension)
    {
        $filter = '\\.['.ltrim($extension, '.').']$';

        $this->addFilter($filter, false, 'file');
    }

    /**
     * Blocks hidden files.
     *
     * @param string $type
     */
    public function blockHidden($type = null)
    {
        $filter = '^\\.';

        $this->addFilter($filter, false, $type);
    }

    /**
     * Allows only hidden files.
     *
     * @param string $type
     */
    public function isHidden($type = null)
    {
        $filter = '^\\.';

        $this->addFilter($filter, true, $type);
    }
}
