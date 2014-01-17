<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Callable importer
 *
 * @package ILess
 * @subpackage import
 */
class ILess_Importer_Callback implements ILess_ImporterInterface
{
    /**
     * Import callback
     *
     * @var callable
     */
    protected $importCallback;

    /**
     * Get last modified callback
     *
     * @var callable
     */
    protected $lastModifiedCallback;

    /**
     * Constructor
     *
     * @param callable $importCallback The import callback
     * @param callable $lastModifiedCallback The "getModified" callback
     * @throws InvalidArgumentException If the callbables are not valid
     */
    public function __construct($importCallback, $lastModifiedCallback)
    {
        $this->assertCallable($importCallback);
        $this->importCallback = $importCallback;
        $this->assertCallable($lastModifiedCallback);
        $this->lastModifiedCallback = $lastModifiedCallback;
    }

    /**
     * Checks if the given $callable is really callable
     *
     * @param mixed $callable The callable
     * @throws InvalidArgumentException If the callable is not valid
     */
    protected function assertCallable($callable)
    {
        if (!is_callable($callable, false, $callableName)) {
            throw new InvalidArgumentException(sprintf('The callable "%s" is not a valid callable.', $callableName));
        }

        return true;
    }

    /**
     * @see ILess_ImporterInterface::import
     */
    public function import($path, ILess_FileInfo $currentFileInfo)
    {
        return call_user_func_array($this->importCallback, array(
            $path, $currentFileInfo
        ));
    }

    /**
     * @see ILess_Importer::getLastModified
     */
    public function getLastModified($path, ILess_FileInfo $currentFileInfo)
    {
        return call_user_func_array($this->lastModifiedCallback, array(
            $path, $currentFileInfo
        ));
    }

}
