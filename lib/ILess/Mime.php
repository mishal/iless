<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Mime lookup
 *
 * @package ILess
 * @subpackage node
 * @todo Implement proper mime detection using Fileinfo
 */
class ILess_Mime
{
    /**
     * Mime types extension map
     *
     * @var array
     */
    public static $types = array(
        '.htm' => 'text/html',
        '.html' => 'text/html',
        '.gif' => 'image/gif',
        '.jpg' => 'image/jpeg',
        '.jpeg' => 'image/jpeg',
        '.png' => 'image/png'
    );

    /**
     * Lookups mime type for the file
     *
     * @param string $filepath The absolute path to a file
     * @return string|null
     */
    public static function lookup($filepath)
    {
        $parts = explode('.', $filepath);
        $ext = '.' . strtolower(array_pop($parts));
        if (!isset(self::$types[$ext])) {
            return;
        }

        return self::$types[$ext];
    }

    /**
     * Lookups the charset for the mime type
     *
     * @param string $type The mime type
     * @return string
     */
    public static function charsetsLookup($type)
    {
        // assumes all text types are UTF-8
        return $type && preg_match('/^text\//', $type) ? 'UTF-8' : '';
    }

}
