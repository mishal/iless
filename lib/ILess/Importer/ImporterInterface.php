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

/**
 * Importer interface.
 */
interface ImporterInterface
{
    /**
     * Imports a file from the path.
     *
     * @param string $path The path to a file
     * @param FileInfo $currentFileInfo The current file information
     *
     * @return ImportedFile
     */
    public function import($path, FileInfo $currentFileInfo);

    /**
     * Returns the last modified timestamp of the import.
     *
     * @param string $path The path to a file
     * @param FileInfo $currentFileInfo The current file information
     *
     * @return int The unix timestamp of last modification
     */
    public function getLastModified($path, FileInfo $currentFileInfo);
}
