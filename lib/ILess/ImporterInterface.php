<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Import
 *
 * @package ILess
 * @subpackage import
 */
interface ILess_ImporterInterface
{
    /**
     * Imports a file from the path
     *
     * @param string $path The path to a file
     * @param ILess_FileInfo $currentFileInfo The current file information
     * @return ILess_ImportedFile
     */
    public function import($path, ILess_FileInfo $currentFileInfo);

    /**
     * Returns the last modified timestamp of the import
     *
     * @param string $path The path to a file
     * @param ILess_FileInfo $currentFileInfo The current file information
     * @return integer The unix timestamp of last modification
     */
    public function getLastModified($path, ILess_FileInfo $currentFileInfo);

}
