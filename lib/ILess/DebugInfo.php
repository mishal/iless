<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess;

/**
 * Debug information.
 */
final class DebugInfo
{
    /**
     * Comment format.
     */
    const FORMAT_COMMENT = 'comments';

    /**
     * Media query format.
     */
    const FORMAT_MEDIA_QUERY = 'mediaquery';

    /**
     * All supported formats.
     */
    const FORMAT_ALL = 'all';

    /**
     * Media query format (SASS compatible format).
     *
     * @var string
     */
    protected static $mediaQueryFormat = "@media -sass-debug-info{filename{font-family:%file%}line{font-family:\\00003%line%}}\n";

    /**
     * The line number.
     *
     * @var int
     */
    public $lineNumber;

    /**
     * Current filename.
     *
     * @var string
     */
    public $filename;

    /**
     * Constructor.
     *
     * @param string $filename The filename
     * @param int $lineNumber The line number
     */
    public function __construct($filename, $lineNumber)
    {
        $this->filename = $filename;
        $this->lineNumber = $lineNumber;
    }

    /**
     * Sets the media query format.
     *
     * @param string $format
     */
    public static function setMediaQueryFormat($format)
    {
        self::$mediaQueryFormat = $format;
    }

    /**
     * Returns the debug information as comment.
     *
     * @return string
     */
    public function getAsComment()
    {
        return sprintf("/* line %d, %s */\n", $this->lineNumber, $this->filename);
    }

    /**
     * Return the debug information as media query.
     *
     * @return string
     */
    public function getAsMediaQuery()
    {
        return strtr(self::$mediaQueryFormat, [
            '%file%' => self::escapeFilenameForMediaQuery(sprintf('file://%s', $this->filename)),
            '%line%' => $this->lineNumber,
        ]);
    }

    /**
     * Replaces path components for media query usage.
     *
     * @param array $match
     *
     * @return string
     */
    protected static function replaceCallback($match)
    {
        $match = $match[0];
        if ($match == '\\') {
            $match = '\/';
        }

        return '\\' . $match;
    }

    /**
     * Escapes the filename for mediaquery usage.
     *
     * @param string $filename The filename
     *
     * @return string
     */
    public static function escapeFilenameForMediaQuery($filename)
    {
        return preg_replace_callback('/([\.|:|\/|(\\\\)])/',
            ['ILess\DebugInfo', 'replaceCallback'], $filename);
    }
}
