<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess;

use ILess\Exception\Exception;

/**
 * PS4 autoloader.
 */
class Autoloader
{
    /**
     * Registered flag.
     *
     * @var bool
     */
    protected static $registered = false;

    /**
     * Library directory.
     *
     * @var string
     */
    protected static $libDir;

    /**
     * Register the autoloader in the spl autoloader.
     *
     * @throws Exception If there was an error in registration
     */
    public static function register()
    {
        if (self::$registered) {
            return;
        }

        self::$libDir = dirname(__DIR__) . '/ILess';

        if (false === spl_autoload_register(['ILess\Autoloader', 'loadClass'])) {
            throw new Exception('Unable to register ILess\Autoloader::loadClass as an autoloading method.');
        }

        self::$registered = true;
    }

    /**
     * Unregisters the autoloader.
     */
    public static function unregister()
    {
        spl_autoload_unregister(['ILess\Autoloader', 'loadClass']);
        self::$registered = false;
    }

    /**
     * Loads the class.
     *
     * @param string $className The class to load
     *
     * @return null|true
     */
    public static function loadClass($class)
    {
        // project-specific namespace prefix
        $prefix = 'ILess\\';

        // does the class use the namespace prefix?
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            // no, move to the next registered autoloader
            return;
        }

        // get the relative class name
        $relativeClass = substr($class, $len);
        // replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, append
        // with .php
        $file = self::$libDir . '/' . str_replace('\\', '/', $relativeClass) . '.php';
        // if the file exists, require it
        if (file_exists($file)) {
            require $file;

            return true;
        }
    }
}
