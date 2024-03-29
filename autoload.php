<?php
/**
 * This class is used for autocomplete.
 * Class _AUTOLOAD_
 * @noautoload it avoids to index this class
 * @generated by AutoLoadOne 1.11 generated 2019/06/03 12:27:22
 * @copyright Copyright Jorge Castro C - MIT License. https://github.com/EFTEC/AutoLoadOne
 * @author Divine Niiquaye <hello@biuhub.net>
 */
const s5cf4696a6fbdd__debug = true;

/* @var string[] Where $_arrautoloadCustom['namespace\Class']='folder\file.php' */
const s5cf4696a6fbdd__arrautoloadCustom = [

];

/* @var string[] Where $_arrautoload['namespace']='folder' */
const s5cf4696a6fbdd__arrautoload = [
	'BiuradPHP\Toolbox\FilePHP' => '/src'
];

/* @var boolean[] Where $_arrautoload['namespace' or 'namespace\Class']=true if it's absolute (it uses the full path) */
const s5cf4696a6fbdd__arrautoloadAbsolute = [
 
];

/**
 * @param $class_name
 * @throws Exception
 */
function s5cf4696a6fbdd__auto($class_name)
{
    // its called only if the class is not loaded.
    $ns = dirname($class_name); // without trailing
    $ns = ($ns == '.') ? '' : $ns;
    $cls = basename($class_name);
    // special cases
    if (isset(s5cf4696a6fbdd__arrautoloadCustom[$class_name])) {
        s5cf4696a6fbdd__loadIfExists(s5cf4696a6fbdd__arrautoloadCustom[$class_name], $class_name);
        return;
    }
    // normal (folder) cases
    if (isset(s5cf4696a6fbdd__arrautoload[$ns])) {
        s5cf4696a6fbdd__loadIfExists(s5cf4696a6fbdd__arrautoload[$ns] . '/' . $cls . '.php', $ns);
        return;
    }
}

/**
 * We load the file.    
 * @param string $filename
 * @param string $key key of the class it could be the full class name or only the namespace
 * @throws Exception
 */
function s5cf4696a6fbdd__loadIfExists($filename, $key)
{
    if (isset(s5cf4696a6fbdd__arrautoloadAbsolute[$key])) {
        $fullFile = $filename; // its an absolute path
        if (strpos($fullFile, '../') === 0) { // Or maybe, not, it's a remote-relative path.
            $oldDir = getcwd();  // we copy the current url
            chdir(__DIR__);
        }
    } else {
        $fullFile = __DIR__ . "/" . $filename; // its relative to this path
    }
    if ((@include $fullFile) === false) {
        if (s5cf4696a6fbdd__debug) {
            throw  new Exception("AutoLoadOne Error: Loading file [" . __DIR__ . "/" . $filename . "] for class [" . basename($filename) . "]");
        } else {
            throw  new Exception("AutoLoadOne Error: No file found.");
        }
    } else {
        if (isset($oldDir)) {
            chdir($oldDir);
        }
    }
}

spl_autoload_register(function ($class_name) {
    s5cf4696a6fbdd__auto($class_name);
});
// autorun

