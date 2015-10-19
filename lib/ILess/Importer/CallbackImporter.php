<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Importer;

use ILess\FileInfo;
use InvalidArgumentException;

/**
 * Callback importer.
 */
class CallbackImporter implements ImporterInterface
{
    /**
     * Import callback.
     *
     * @var callable
     */
    protected $importCallback;

    /**
     * Get last modified callback.
     *
     * @var callable
     */
    protected $lastModifiedCallback;

    /**
     * Constructor.
     *
     * @param callable $importCallback The import callback
     * @param callable $lastModifiedCallback The "getModified" callback
     *
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
     * Checks if the given $callable is really callable.
     *
     * @param mixed $callable The callable
     *
     * @throws InvalidArgumentException If the callable is not valid
     *
     * @return bool
     */
    protected function assertCallable($callable)
    {
        if (!is_callable($callable, false, $callableName)) {
            throw new InvalidArgumentException(sprintf('The callable "%s" is not a valid callable.', $callableName));
        }

        return true;
    }

    /**
     * @see ImporterInterface::import
     */
    public function import($path, FileInfo $currentFileInfo)
    {
        return call_user_func_array($this->importCallback, [
            $path,
            $currentFileInfo,
        ]);
    }

    /**
     * @see Importer::getLastModified
     */
    public function getLastModified($path, FileInfo $currentFileInfo)
    {
        return call_user_func_array($this->lastModifiedCallback, [
            $path,
            $currentFileInfo,
        ]);
    }
}
