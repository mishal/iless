<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess;

/**
 * File information
 *
 * @package ILess
 */
final class FileInfo
{
    /**
     * Full resolved filename of current file
     *
     * @var string
     */
    public $filename;

    /**
     * Path to append to normal URLs for this node
     *
     * @var string
     */
    public $rootPath;

    /**
     * Path to the current file, absolute
     *
     * @var string
     */
    public $currentDirectory;

    /**
     * Filename of the base file
     *
     * @var string
     */
    public $rootFilename;

    /**
     * Absolute path to the entry directory
     *
     * @var string
     */
    public $entryPath;

    /**
     * Whether the file should not be output and only output parts that are referenced
     *
     * @var boolean
     */
    public $reference = false;

    /**
     * The imported file
     *
     * @var ImportedFile
     */
    public $importedFile;

    /**
     * Constructor
     *
     * @param array $info
     */
    public function __construct(array $info = array())
    {
        foreach ($info as $property => $value) {
            $this->$property = $value;
        }
    }

}
