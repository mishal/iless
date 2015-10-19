<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Util;

/**
 * Mime lookup utility.
 */
final class Mime
{
    /**
     * @var resource
     */
    private static $finfo;

    /**
     * Lookup mime type for the file.
     *
     * @param string $path The absolute path to a file
     *
     * @return string|null
     */
    public static function lookup($path)
    {
        return finfo_file(self::getFileInfoHandle(), $path, FILEINFO_MIME_TYPE);
    }

    /**
     * Lookup the charset for the mime type.
     *
     * @param string $type The mime type
     *
     * @return string
     */
    public static function charsetsLookup($type)
    {
        // assumes all text types are UTF-8
        return $type && preg_match('/^text\//', $type) ? 'UTF-8' : '';
    }

    /**
     * Returns the file info handle.
     *
     * @return resource
     */
    private static function getFileInfoHandle()
    {
        if (!self::$finfo) {
            self::$finfo = finfo_open(FILEINFO_MIME);
        }

        return self::$finfo;
    }
}
