<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Autoloader
 *
 * @package ILess
 * @subpackage autoload
 */
class ILess_Autoloader
{
    /**
     * Registered flag
     *
     * @var boolean
     */
    protected static $registered = false;

    /**
     * Library directory
     *
     * @var string
     */
    protected static $libDir;

    /**
     * Register the autoloader in the spl autoloader
     *
     * @return void
     * @throws Exception If there was an error in registration
     */
    public static function register()
    {
        if (self::$registered) {
            return;
        }

        self::$libDir = dirname(dirname(__FILE__));

        if (false === spl_autoload_register(array('ILess_Autoloader', 'loadClass'))) {
            throw new Exception('Unable to register ILess_Autoloader::loadClass as an autoloading method.');
        }

        self::$registered = true;
    }

    /**
     * Unregisters the autoloader
     *
     * @return void
     */
    public static function unregister()
    {
        spl_autoload_unregister(array('ILess_Autoloader', 'loadClass'));
        self::$registered = false;
    }

    /**
     * Loads the class
     *
     * @param string $className The class to load
     */
    public static function loadClass($className)
    {
        // handle only package classes
        if (strpos($className, 'ILess_') !== 0) {
            return;
        }

        $fileName = self::$libDir . '/' . str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        if (file_exists($fileName)) {
            require $fileName;

            return true;
        }
    }

}
