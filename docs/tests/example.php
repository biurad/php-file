<?php

use BiuradPHP\Toolbox\FilePHP\FileHandler;

require __DIR__.'/../../autoload.php';
$filehandler = new FileHandler();

echo FileHandler::getInstance(__DIR__)->load();
echo '<br />';
echo FileHandler::getInstance(__DIR__.'/data/data.txt')->load();
echo '<br />';
FileHandler::getInstance(__DIR__.'/data/sample.txt')->replace(['Sample', 'Hi', 'Hello']);
echo FileHandler::getInstance(__DIR__.'/data/sample.txt')->get();
echo '<br />';
echo FileHandler::getInstance(__DIR__.'/data/data-1.txt')->get(false);
echo '<br />';
echo FileHandler::getInstance(__DIR__.'/data.txt')->basename();
echo '<br />';
if (FileHandler::getInstance(__DIR__.'/data/data-1.txt')->exists()) {
    FileHandler::getInstance(__DIR__)->match_extended('/*');
    echo FileHandler::getInstance(__DIR__.'/data/data-1.txt')->copy(__DIR__.'/../data');
    echo '<br />';
    echo FileHandler::getInstance(__DIR__.'/../data')->delete();
}
echo '<br />';
echo FileHandler::getInstance(__DIR__.'/playground')->mkdir();
echo '<br />';
echo FileHandler::getInstance(__DIR__.'/playground')->is_empty();
echo '<br />';
echo FileHandler::getInstance(__DIR__.'/playground')->remove_dir();
echo '<br />';
echo FileHandler::getInstance(__DIR__.'/../../composer.json')->get(true);
echo '<br />';
echo FileHandler::getInstance(__DIR__.'/../../')->size();
echo '<br />';
echo FileHandler::getInstance(__DIR__)->free_space();
echo '<br />';
echo FileHandler::getInstance(__DIR__)->total_space();
echo '<br />';
echo FileHandler::getInstance(__DIR__)->readable();
echo '<br />';
echo FileHandler::getInstance(__DIR__)->writable();
echo '<br />';
echo FileHandler::getInstance(__DIR__)->executable();
echo '<br />';
echo FileHandler::getInstance(__DIR__.'/data/data-1.txt')->is_file();
echo '<br /> ';
echo FileHandler::getInstance(__DIR__)->is_dir();
echo '<br />';
echo FileHandler::getInstance(__DIR__)->dirname();
echo '<br />';
echo FileHandler::getInstance(__DIR__.'/data/data-2.txt')->passthru();
echo '<br />';
echo FileHandler::getInstance(__DIR__.'/data/data-1.txt')->permission();
echo '<br />';
echo FileHandler::getInstance(__DIR__.'/data/data.txt')->rename('data.txt');
echo '<br />';
echo FileHandler::getInstance(__DIR__.'/data/data-1.txt')->readfile();
echo '<br />';
echo FileHandler::getInstance(__DIR__.'/data/data-1.txt')->short_name();
echo '<br />';
echo FileHandler::getInstance(__DIR__.'/../../')->count_dir();
echo '<br />';
echo FileHandler::getInstance(__DIR__.'/data/data-1.txt')->open();
echo '<br />';
$microtime = microtime(true);
$time = 0;
echo round(($microtime - $time) * 1000) / 1000;
//echo FileHandler::getInstance(__DIR__.'/data')->scan_dir();
echo '<br />';
//echo FileHandler::getInstance(__DIR__.'/example.php')->file();
echo '<br />';
//echo FileHandler::getInstance(__DIR__)->info();