<?php

/*
 * The Biurad Library Autoload via cli.
 *
 * This is an extensible library used to load classes
 * from namespaces and files just like composer.
 * But this is built in procedural php.
 *
 * @see ReadMe.md to know more about how to load your
 * classes via command line.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 */

namespace BiuradPHP\Toolbox\FilePHP\Provider;

use BiuradPHP\Toolbox\FilePHP\File;
use BiuradPHP\Toolbox\FilePHP\Filter;
use BiuradPHP\Toolbox\FilePHP\Finder;
use BiuradPHP\Toolbox\FilePHP\Directory;
use BiuradPHP\Framework\Service\ServiceProcessor;
use BiuradPHP\Framework\Core\Interfaces\ApplicationInterface as App;

class FilePHPProcessor extends ServiceProcessor
{
    public function __construct(App $app)
    {
        return parent::__construct($app, Filter::class);
    }

    public function register()
    {
        $this->getFile();
        $this->getDirectory();

        $this->app->singleton('filesystem.filter', function ($app) {
            return $app->make($this->getProvider());
        });

        $this->app->singleton('filesystem.finder', function ($app) {
            return $app->make(Finder::class);
        });
    }

    /**
     * The filesystem.
     *
     * @return void
     */
    protected function getFile()
    {
        $this->app->singleton('filesystem.file', function ($app) {
            return $app->make(File::class);
        });
    }

    /**
     * The directorysystem.
     *
     * @return void
     */
    protected function getDirectory()
    {
        $this->app->singleton('filesystem.directory', function ($app) {
            return $app->make(Directory::class);
        });
    }

    public function boot()
    {
        //
    }
}
