<?php

/*
 * The Biurad Toolbox ConsoleLite.
 *
 * This is an extensible library used to load classes
 * from namespaces and files just like composer.
 *
 * @see ReadMe.md to know more about how to load your
 * classes via command line.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 */

namespace BiuradPHP\Toolbox\FilePHP;

/**
 * Temporarily suppress PHP error reporting, usually warnings and below.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class Silencer
{
    /**
     * Calls a specified function while silencing warnings and below.
     *
     * Future improvement: when PHP requirements are raised add Callable type hint (5.4) and variadic parameters (5.6)
     *
     * @param callable $callable function to execute
     *
     * @throws \Exception any exceptions from the callback are rethrown
     *
     * @return mixed return value of the callback
     */
    public function call($callable /*, ...$parameters */)
    {
        try {
            $result = call_user_func_array($callable, array_slice(func_get_args(), 1));

            if ($result === 'eval' || $callable === 'eval') {
                throw new \BadFunctionCallException('The function eval should never be used');
            }

            return $result;
        } catch (\Exception $e) {
            // Use a finally block for this when requirements are raised to PHP 5.5
            throw $e;
        }
    }
}
