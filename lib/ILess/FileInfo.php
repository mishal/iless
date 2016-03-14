<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess;

use ILess\Util\Serializer;

/**
 * File information.
 */
final class FileInfo implements \Serializable
{
    /**
     * Full resolved filename of current file.
     *
     * @var string
     */
    public $filename;

    /**
     * Path to append to normal URLs for this node.
     *
     * @var string
     */
    public $rootPath;

    /**
     * Path to the current file, absolute.
     *
     * @var string
     */
    public $currentDirectory;

    /**
     * Filename of the base file.
     *
     * @var string
     */
    public $rootFilename;

    /**
     * Absolute path to the entry directory.
     *
     * @var string
     */
    public $entryPath;

    /**
     * Whether the file should not be output and only output parts that are referenced.
     *
     * @var bool
     */
    public $reference = false;

    /**
     * The imported file.
     *
     * @var ImportedFile
     */
    public $importedFile;

    /**
     * Constructor.
     *
     * @param array $info
     */
    public function __construct(array $info = [])
    {
        foreach ($info as $property => $value) {
            if (!property_exists($this, $property)) {
                throw new \InvalidArgumentException(sprintf('Invalid property %s', $property));
            }
            $this->$property = $value;
        }
    }

    public function serialize()
    {
        $vars = get_object_vars($this);

        unset($vars['reference']);
        unset($vars['rootPath']);

        return Serializer::serialize($vars);
    }

    public function unserialize($serialized)
    {
        $unserialized = Serializer::unserialize($serialized);

        foreach ($unserialized as $var => $val) {
            $this->$var = $val;
        }
    }
}
