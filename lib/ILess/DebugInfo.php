<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Debug information
 *
 * @package ILess
 * @subpackage debug
 */
class ILess_DebugInfo
{
    /**
     * Comment format
     */
    const FORMAT_COMMENT = 'comment';

    /**
     * Media query format
     */
    const FORMAT_MEDIA_QUERY = 'mediaquery';

    /**
     * All supported formats
     *
     */
    const FORMAT_ALL = 'all';

    /**
     * Media query format (SASS compatible format)
     *
     * @var string
     */
    protected static $mediaQueryFormat = '@media -sass-debug-info{filename{font-family:%file%}line{font-family:\00003%line%}}';

    /**
     * The line number
     *
     * @var integer
     */
    public $lineNumber;

    /**
     * Current filename
     *
     * @var string
     */
    public $filename;

    /**
     * Constructor
     *
     * @param string $filename The filename
     * @param integer $lineNumber The line number
     */
    public function __construct($filename, $lineNumber)
    {
        $this->filename = $filename;
        $this->lineNumber = $lineNumber;
    }

    /**
     * Sets the media query format
     *
     * @param string $format
     */
    public static function setMediaQueryFormat($format)
    {
        self::$mediaQueryFormat = $format;
    }

    /**
     * Returns the debug information as comment
     *
     * @return string
     */
    public function getAsComment()
    {
        return sprintf("/* line %d, %s */\n", $this->lineNumber, $this->filename);
    }

    /**
     * Return the debug information as media query
     *
     * @return string
     */
    public function getAsMediaQuery()
    {
        return strtr(self::$mediaQueryFormat, array(
            '%file%' => preg_replace_callback('/([\.|:|\/|(\\\\)])/',
                array($this, 'replaceCallback'), sprintf('file://%s', $this->filename)),
            '%line%' => $this->lineNumber
        ));
    }

    /**
     * Replaces path components for media query usage
     *
     * @param array $match
     * @return string
     */
    protected function replaceCallback($match)
    {
        $match = $match[0];
        if ($match == '\\') {
            $match = '\/';
        }

        return '\\' . $match;
    }

}
