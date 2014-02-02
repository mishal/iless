<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * ANSIColor
 *
 * @package ILess
 * @subpackage exception
 */
class ILess_ANSIColor
{
    /**
     * Array of ANSI codes
     *
     * @var array
     */
    protected static $codes = array(
        'reset' => array(0, 0),
        'bold' => array(1, 22),
        'inverse' => array(7, 27),
        'underline' => array(4, 24),
        'yellow' => array(33, 39),
        'green' => array(32, 39),
        'red' => array(31, 39),
        'grey' => array(90, 39)
    );

    /**
     * Colorizes the string by given style
     *
     * @param string $string The string to colorize
     * @param string $style The style
     * @return string
     */
    public static function colorize($string, $style)
    {
        $styles = explode('+', $style);
        $colorized = '';
        foreach ($styles as $s) {
            $colorized .= "\033[" . self::$codes[$s][0] . 'm';
        }
        $colorized .= $string . "\033[" . self::$codes[$s][1] . 'm';

        return $colorized;
    }

}