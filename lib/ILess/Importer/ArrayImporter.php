<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Importer;

use ILess\FileInfo;
use ILess\ImportedFile;
use ILess\Util;

/**
 * Array importer.
 */
class ArrayImporter implements ImporterInterface
{
    /**
     * An array of files.
     *
     * @var array
     */
    protected $files = [];

    /**
     * An array of last modified timestamp.
     *
     * @var array
     */
    protected $lastModified = [];

    /**
     * Constructor.
     *
     * @param array $files An array of files (keys are the names, and values are the source code)
     * @param array $lastModified An array of last modified (keys are the names, and values are the timestamp)
     */
    public function __construct(array $files, array $lastModified = [])
    {
        $this->files = $files;
        $this->lastModified = $lastModified + array_fill_keys(array_keys($files), -1);
    }

    /**
     * Adds or overrides a file.
     *
     * @param string $path The file name
     * @param string $content The file source
     * @param int $lastModified The file last modified timestamp
     *
     * @return ArrayImporter
     */
    public function setFile($path, $content, $lastModified = -1)
    {
        $this->files[$path] = $content;
        $this->lastModified[$path] = $lastModified;

        return $this;
    }

    /**
     * @see ImporterInterface::import
     */
    public function import($path, FileInfo $currentFileInfo)
    {
        $normalizedPath = Util::normalizePath($currentFileInfo->currentDirectory . $path);
        if (isset($this->files[$normalizedPath])) {
            $path = $normalizedPath;
        }

        if (isset($this->files[$path])) {
            return new ImportedFile($path, $this->files[$path], $this->lastModified[$path]);
        }

        return false;
    }

    /**
     * @see Importer::getLastModified
     */
    public function getLastModified($path, FileInfo $currentFileInfo)
    {
        $normalizedPath = Util::normalizePath($currentFileInfo->currentDirectory . $path);
        if (isset($this->files[$normalizedPath])) {
            $path = $normalizedPath;
        }

        if (isset($this->lastModified[$path])) {
            return $this->lastModified[$path];
        }

        return false;
    }
}
