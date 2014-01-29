<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Array importer
 *
 * @package ILess
 * @subpackage import
 */
class ILess_Importer_Array implements ILess_ImporterInterface
{
    /**
     * An array of files
     *
     * @var array
     */
    protected $files = array();

    /**
     * An array of last modified timestamp
     *
     * @var array
     */
    protected $lastModified = array();

    /**
     * Constructor
     *
     * @param array $files        An array of files (keys are the names, and values are the source code)
     * @param array $lastModified An array of last modified (keys are the names, and values are the timestamp)
     */
    public function __construct(array $files, array $lastModified = array())
    {
        $this->files = $files;
        $this->lastModified = $lastModified + array_fill_keys(array_keys($files), -1);
    }

    /**
     * Adds or overrides a file.
     *
     * @param string  $path         The file name
     * @param string  $content      The file source
     * @param integer $lastModified The file last modified timestamp
     * @return ILess_Importer_Array
     */
    public function setFile($path, $content, $lastModified = -1)
    {
        $this->files[$path] = $content;
        $this->lastModified[$path] = $lastModified;

        return $this;
    }

    /**
     * @see ILess_ImporterInterface::import
     */
    public function import($path, ILess_FileInfo $currentFileInfo)
    {
        $normalizedPath = ILess_Util::normalizePath($currentFileInfo->currentDirectory.$path);
        if (isset($this->files[$normalizedPath])) {
            $path = $normalizedPath;
        }

        if (isset($this->files[$path])) {
            return new ILess_ImportedFile($path, $this->files[$path], $this->lastModified[$path]);
        }

        return false;
    }

    /**
     * @see ILess_Importer::getLastModified
     */
    public function getLastModified($path, ILess_FileInfo $currentFileInfo)
    {
        $normalizedPath = ILess_Util::normalizePath($currentFileInfo->currentDirectory.$path);
        if (isset($this->files[$normalizedPath])) {
            $path = $normalizedPath;
        }

        if (isset($this->lastModified[$path])) {
            return $this->lastModified[$path];
        }

        return false;
    }

}
