<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Importer;

use ILess\Configurable;
use ILess\FileInfo;
use ILess\ImportedFile;
use ILess\Util;

/**
 * File system importer.
 *
 * # Possible options
 *
 * `clear_stat_cache` (boolean) - clear stat cache?
 */
class FileSystemImporter extends Configurable implements ImporterInterface
{
    /**
     * Array of import paths.
     *
     * @var array
     */
    protected $importDirs = [];

    /**
     * Array of default options.
     *
     * @var array
     */
    protected $defaultOptions = [
        'clear_stat_cache' => true, // clear the stat cache?
    ];

    /**
     * Constructor.
     *
     * @param array $importDirs Array of import paths to search
     * @param array $options Array of options
     */
    public function __construct($importDirs = [], $options = [])
    {
        $this->importDirs = (array) $importDirs;
        parent::__construct($options);
    }

    /**
     * Setups the importer.
     */
    protected function setup()
    {
        if ($this->getOption('clear_stat_cache')) {
            clearstatcache();
        }
    }

    /**
     * @see ImporterInterface::import
     */
    public function import($path, FileInfo $currentFileInfo)
    {
        if ($file = $this->find($path, $currentFileInfo)) {
            return new ImportedFile($file, file_get_contents($file), filemtime($file));
        }

        return false;
    }

    /**
     * @see Importer::getLastModified
     */
    public function getLastModified($path, FileInfo $currentFileInfo)
    {
        if ($file = $this->find($path, $currentFileInfo)) {
            return filemtime($file);
        }

        return false;
    }

    /**
     * Tries to find a file.
     *
     * @param string $path The path to a file
     * @param FileInfo $currentFileInfo
     *
     * @return string|false
     */
    protected function find($path, FileInfo $currentFileInfo)
    {
        if (Util::isPathAbsolute($path) && is_readable($path)) {
            return realpath($path);
        } elseif (is_readable($currentFileInfo->currentDirectory . $path)) {
            return realpath($currentFileInfo->currentDirectory . $path);
        }

        // try import dirs
        foreach ($this->importDirs as $importDir) {
            if (is_readable($importDir . '/' . $path)) {
                return realpath($importDir . '/' . $path);
            }
        }

        return false;
    }

    /**
     * Returns an array of import paths.
     *
     * @return array
     */
    public function getImportPaths()
    {
        return $this->importDirs;
    }

    /**
     * Adds import directory.
     *
     * @param string $dir The directory
     * @param bool $prepend Prepend?
     *
     * @return FileSystemImporter
     */
    public function addImportDir($dir, $prepend = false)
    {
        if ($prepend) {
            array_unshift($this->importDirs, $dir);
        } else {
            $this->importDirs[] = $dir;
        }

        return $this;
    }

    /**
     * Set import directories.
     *
     * @param array $dirs
     *
     * @return FileSystemImporter
     */
    public function setImportDirs($dirs)
    {
        $this->importDirs = (array) $dirs;

        return $this;
    }
}
